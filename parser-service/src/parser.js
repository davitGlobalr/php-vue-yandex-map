import { politeWait, sleep, jitter } from './utils.js';

export async function parsePlaceSummary(page) {
    const result = { title: null, rating_value: null, rating_count: null };
    try {
        await politeWait();
        await sleep(800);

        const titleSelectors = [
            'h1.card-title-view__title',
            '.card-title-view__wrapper h1',
            'h1.orgpage-header-view__header',
            '.orgpage-header-view__header-wrapper h1',
            '.orgpage-header-view__header-wrapper h1[itemprop="name"]',
            'h1[itemprop="name"]',
        ];
        for (const sel of titleSelectors) {
            const n = await page.locator(sel).count();
            if (n > 0) {
                const text = await page.locator(sel).first().innerText().catch(() => null);
                if (text && text.trim()) {
                    result.title = text.trim();
                    break;
                }
            }
        }

        try {
            const ariaEls = await page.locator('[aria-label*="Rating"], [aria-label*="Оценка"]').all();
            for (const el of ariaEls) {
                const aria = await el.getAttribute('aria-label').catch(() => null);
                if (aria) {
                    const m =
                        aria.match(/Rating\s+([\d.,]+)\s+Out of 5/i) ||
                        aria.match(/Оценка\s+([\d.,]+)\s+из\s+5/i) ||
                        aria.match(/([\d.,]+)\s*\/\s*5/i);
                    if (m) {
                        result.rating_value = parseFloat(m[1].replace(',', '.')) || null;
                        if (result.rating_value >= 1 && result.rating_value <= 5) {
                            break;
                        }
                    }
                }
            }
        } catch (e) {
        }

        if (result.rating_value == null) {
            try {
                const ratingTextElements = await page
                    .locator('.business-summary-rating-badge-view__rating-text')
                    .all();
                if (ratingTextElements.length >= 2) {
                    const parts = [];
                    for (const element of ratingTextElements) {
                        const text = await element.innerText().catch(() => null);
                        if (text) parts.push(text.trim());
                    }
                    if (parts.length >= 2) {
                        const ratingStr = parts.join('').replace(',', '.');
                        const parsed = parseFloat(ratingStr);
                        if (parsed >= 1 && parsed <= 5) {
                            result.rating_value = parsed;
                        }
                    }
                }
            } catch (e) {
            }
        }

        const countSelectors = [
            '.business-rating-amount-view._summary',
            '.business-summary-rating-badge-view__rating-count .business-rating-amount-view',
            '.business-rating-amount-view',
        ];
        for (const sel of countSelectors) {
            const n = await page.locator(sel).count();
            if (n > 0) {
                const countText = await page.locator(sel).first().innerText().catch(() => null);
                if (countText) {
                    const m = countText.match(/(\d+)\s*(ratings|оценок|оценки)?/i);
                    if (m) {
                        result.rating_count = parseInt(m[1], 10) || null;
                        break;
                    }
                }
            }
        }

        return result;
    } catch (e) {
        return { title: null, rating_value: null, rating_count: null };
    }
}

export async function parseReviews(page, max = 50) {
    const reviewCard = '.business-review-view';

    try {
        await page.waitForSelector(reviewCard, { timeout: 60000 });
    } catch (e) {
        const alternativeSelectors = [
            '[class*="review"]',
            '[class*="Review"]',
            '.review',
            '[itemprop="review"]',
        ];
        let found = false;
        for (const selector of alternativeSelectors) {
            try {
                await page.waitForSelector(selector, { timeout: 10000 });
                found = true;
                break;
            } catch (err) {}
        }
        if (!found) {
            await sleep(3000);
            const bodyText = await page.textContent('body').catch(() => '');
            if (!bodyText || bodyText.length < 100) {
                throw new Error('Page content not loaded properly');
            }
        }
    }

    let prevCount = 0;
    let stableCount = 0;
    const maxScrollIterations = 80;

    for (let i = 0; i < maxScrollIterations; i++) {
        const count = await page.locator(reviewCard).count();

        if (count === 0 && i === 0) {
            break;
        }

        if (count === prevCount) {
            stableCount++;
            if (stableCount >= 3) {
                break;
            }
        } else {
            stableCount = 0;
        }

        if (count >= max) {
            break;
        }
        prevCount = count;

        if (count > 0) {
            try {
                const lastCard = page.locator(reviewCard).last();
                await lastCard.scrollIntoViewIfNeeded({ timeout: 5000 });
                await politeWait();
            } catch (e) {
            }
        }

        await page.mouse.wheel(0, jitter(400, 900));
        await sleep(jitter(600, 1400));
    }

    const cards = page.locator(reviewCard);
    const count = await cards.count();

    if (count === 0) {
        const alternativeSelectors = [
            '[class*="review"]',
            '[class*="Review"]',
            '.review-item',
            '[itemprop="review"]',
        ];
        for (const altSelector of alternativeSelectors) {
            const altCount = await page.locator(altSelector).count();
            if (altCount > 0) {
                return [];
            }
        }
        return [];
    }

    const finalCount = Math.min(count, max);

    const results = [];
    for (let i = 0; i < count; i++) {
        try {
            const card = cards.nth(i);

            const userProfileUrl = await card
                .locator('a.business-review-view__user-icon')
                .getAttribute('href')
                .catch(() => null);
            const userUid =
                userProfileUrl
                    ?.split('/maps/user/')?.[1]
                    ?.split('?')[0]
                    ?.replaceAll('/', '') || null;

            let finalUserUid = userUid;
            if (!finalUserUid) {
                const altUrl = await card
                    .locator("a[href*='/maps/user/']")
                    .first()
                    .getAttribute('href')
                    .catch(() => null);
                if (altUrl) {
                    finalUserUid =
                        altUrl
                            .split('/maps/user/')?.[1]
                            ?.split('?')[0]
                            ?.split('/')[0] || null;
                }
            }

            let userName = await card
                .locator('[itemprop="author"] [itemprop="name"]')
                .innerText()
                .catch(() => null);

            if (!userName) {
                const altName = await card
                    .locator('.business-review-view__author-name')
                    .innerText()
                    .catch(() => null);
                if (altName) {
                    userName = altName.trim();
                }
            }

            let ratingValue = await card
                .locator(
                    '[itemprop="reviewRating"] meta[itemprop="ratingValue"]',
                )
                .getAttribute('content')
                .catch(() => null);

            if (!ratingValue) {
                const ratingStars = await card
                    .locator('[itemprop="reviewRating"]')
                    .getAttribute('content')
                    .catch(() => null);
                if (ratingStars) {
                    ratingValue = ratingStars;
                }
            }

            let publishedAt = await card
                .locator('meta[itemprop="datePublished"]')
                .getAttribute('content')
                .catch(() => null);

            if (!publishedAt) {
                const dateText = await card
                    .locator('.business-review-view__date')
                    .getAttribute('datetime')
                    .catch(() => null);
                if (dateText) {
                    publishedAt = dateText;
                }
            }

            let body = await card
                .locator('[itemprop="reviewBody"]')
                .innerText()
                .catch(() => null);

            if (!body) {
                const altBody = await card
                    .locator('.business-review-view__text')
                    .innerText()
                    .catch(() => null);
                if (altBody) {
                    body = altBody.trim();
                }
            }

            results.push({
                user_uid: finalUserUid,
                user_profile_url: userProfileUrl,
                user_name: userName,
                rating: ratingValue ? Math.round(Number(ratingValue)) : null,
                published_at: publishedAt,
                body: body?.trim() || null,
            });
        } catch (e) {
        }
    }

    const withUid = results.filter((r) => r.user_uid).length;
    return results;
}
