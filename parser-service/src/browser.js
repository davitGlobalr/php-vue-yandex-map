import { chromium } from "playwright";
import { getProfileDir } from "./storage.js";

const USE_HEADLESS = process.env.USE_HEADLESS !== "false";

// Realistic Chrome on Windows — reduces automation signals
const USER_AGENT =
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36";

function getProxyConfig() {
  const server = process.env.HTTP_PROXY || process.env.HTTPS_PROXY;
  if (!server) return undefined;
  return {
    server: server.startsWith("http") ? server : `http://${server}`,
    username: process.env.PROXY_USERNAME,
    password: process.env.PROXY_PASSWORD,
  };
}

export async function openBrowser() {
  const userDataDir = getProfileDir();

  const launchOptions = {
    headless: USE_HEADLESS,
    locale: "ru-RU",
    timezoneId: "Europe/Moscow",
    viewport: { width: 1920, height: 1080 },
    userAgent: USER_AGENT,
    args: [
      "--disable-dev-shm-usage",
      "--no-sandbox",
      "--disable-setuid-sandbox",
      "--disable-blink-features=AutomationControlled",
      "--disable-infobars",
      "--window-size=1920,1080",
    ],
    ignoreDefaultArgs: ["--enable-automation"],
  };

  const proxy = getProxyConfig();
  if (proxy) {
    launchOptions.proxy = proxy;
  }

  const context = await chromium.launchPersistentContext(userDataDir, launchOptions);

  const page = await context.newPage();
  await page.setExtraHTTPHeaders({
    "Accept-Language": "ru-RU,ru;q=0.9,en;q=0.8",
  });

  await page.addInitScript(() => {
    Object.defineProperty(navigator, "webdriver", { get: () => undefined });
  });

  return { context, page };
}
