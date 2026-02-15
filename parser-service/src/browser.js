import { chromium } from "playwright";
import { getProfileDir } from "./storage.js";

const USE_HEADLESS = process.env.USE_HEADLESS !== "false";

export async function openBrowser() {
  const userDataDir = getProfileDir();

  const context = await chromium.launchPersistentContext(userDataDir, {
    headless: USE_HEADLESS,
    locale: "en-US",
    timezoneId: "Europe/Moscow",
    viewport: { width: 1280, height: 800 },
    args: [
      "--disable-dev-shm-usage",
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-blink-features=AutomationControlled",
    ],
    ignoreDefaultArgs: ["--enable-automation"],
  });

  const page = await context.newPage();
  await page.setExtraHTTPHeaders({
    "Accept-Language": "en-US,en;q=0.9,ru;q=0.8",
  });

  return { context, page };
}
