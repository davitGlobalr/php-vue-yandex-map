import express from 'express';
import crypto from 'crypto';
import fs from 'fs';
import path from 'path';
import { ensureDataDir, appendJsonl, saveScreenshot } from './storage.js';
import { parseReviews, parsePlaceSummary } from './parser.js';
import { openBrowser } from './browser.js';
import { isCaptcha, politeWait } from './utils.js';

ensureDataDir();

const app = express();
app.use(express.json({ limit: '1mb' }));

app.get('/health', (_, res) => res.json({ ok: true }));

const jobQueue = [];
let isProcessing = false;

async function processQueue() {
    if (isProcessing || jobQueue.length === 0) return;
    isProcessing = true;
    const job = jobQueue.shift();
    try {
        await runParseJob(job);
    } finally {
        isProcessing = false;
        if (jobQueue.length > 0) processQueue();
    }
}

const BACKOFF_MS = [10000, 30000, 120000];

async function notifyLaravel(payload, endpoint = 'import') {
    const laravelWebhookUrl =
        process.env.LARAVEL_WEBHOOK_URL || 'http://nginx:80';
    const webhookSecret = process.env.PARSER_WEBHOOK_SECRET;
    if (!webhookSecret) {
        return false;
    }
    const body = JSON.stringify(payload);
    const signature = crypto
        .createHmac('sha256', webhookSecret)
        .update(body)
        .digest('hex');
    try {
        const res = await fetch(`${laravelWebhookUrl}/api/parser/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Parser-Signature': signature,
            },
            body,
        });
        if (!res.ok) {
            return false;
        }
        return true;
    } catch (e) {
        return false;
    }
}

async function runParseJob({ place_id, url, max }) {
    const filePath = `${place_id}.jsonl`;
    let captchaCount = 0;

    for (let attempt = 0; attempt < 3; attempt++) {
        if (attempt > 0) {
            const delay =
                BACKOFF_MS[Math.min(attempt - 1, BACKOFF_MS.length - 1)];
            await new Promise((r) => setTimeout(r, delay));
        }

        let context, page;
        try {
            const browser = await openBrowser();
            context = browser.context;
            page = browser.page;

            await page.route('**/*', (route) => {
                const rt = route.request().resourceType();
                if (rt === 'image' || rt === 'media' || rt === 'font')
                    return route.abort();
                route.continue();
            });

            await page.goto(url, {
                waitUntil: 'domcontentloaded',
                timeout: 60000,
            });
            await politeWait();

            if (await isCaptcha(page)) {
                captchaCount++;
                const screenshotPath = saveScreenshot(place_id, 'captcha');
                await page.screenshot({ path: screenshotPath }).catch(() => {});
                await page.close().catch(() => {});
                await context.close().catch(() => {});

                if (captchaCount >= 2) {
                    console.log('[FLOW] 2 CAPTCHAs in a row → needs_manual');
                    await notifyLaravel(
                        {
                            place_id: String(place_id),
                            reason: 'captcha',
                            screenshot_path: path.basename(screenshotPath),
                        },
                        'needs-manual',
                    );
                    return;
                }
                continue;
            }

            let placeSummary = await parsePlaceSummary(page);
            if (!placeSummary.rating_value && !placeSummary.rating_count) {
                const mainUrl = url.replace(/\/reviews.*$/, '');
                await page.goto(mainUrl, {
                    waitUntil: 'domcontentloaded',
                    timeout: 30000,
                });
                await politeWait();
                placeSummary = await parsePlaceSummary(page);
            }

            if (!page.url().includes('/reviews')) {
                await page.goto(url, {
                    waitUntil: 'domcontentloaded',
                    timeout: 30000,
                });
                await politeWait();
            }

            if (await isCaptcha(page)) {
                captchaCount++;
                continue;
            }

            const reviews = await parseReviews(page, max);
            let savedCount = 0;

            for (const r of reviews) {
                let finalUserUid = r.user_uid;
                if (!finalUserUid && r.user_name && r.published_at) {
                    const nameHash = r.user_name
                        .toLowerCase()
                        .replace(/\s+/g, '_')
                        .substring(0, 20);
                    const datePart = (r.published_at || '')
                        .substring(0, 10)
                        .replace(/-/g, '');
                    finalUserUid = `temp_${nameHash}_${datePart}`;
                }
                if (!finalUserUid) continue;

                appendJsonl(place_id, {
                    place_id,
                    ...r,
                    user_uid: finalUserUid,
                    scraped_at: new Date().toISOString(),
                    source_url: url,
                    place_title:
                        savedCount === 0 ? placeSummary.title : undefined,
                    place_rating_value:
                        savedCount === 0
                            ? placeSummary.rating_value
                            : undefined,
                    place_rating_count:
                        savedCount === 0
                            ? placeSummary.rating_count
                            : undefined,
                });
                savedCount++;
            }

            await page.close().catch(() => {});
            await context.close().catch(() => {});

            await notifyLaravel(
                { place_id: String(place_id), file_path: filePath },
                'import',
            );
            return;
        } catch (e) {
            appendJsonl(place_id, {
                place_id,
                error: String(e?.message || e),
                scraped_at: new Date().toISOString(),
                source_url: url,
            });
            try {
                if (page) await page.close();
                if (context) await context.close();
            } catch (_) {}
        }
    }

    await notifyLaravel(
        { place_id: String(place_id), reason: 'max_retries_exceeded' },
        'needs-manual',
    );
}

app.post('/jobs', async (req, res) => {
    const { place_id, url, max = 50 } = req.body || {};
    if (!place_id || !url) {
        return res.status(400).json({ error: 'place_id and url are required' });
    }

    const jobId = `${place_id}-${Date.now()}`;

    res.status(202).json({ job_id: jobId, status: 'queued' });

    jobQueue.push({ place_id, url, max: max || 100 });
    processQueue();
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`parser-service on :${port}`));
