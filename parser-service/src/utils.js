
export const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

export const jitter = (min, max) => min + Math.floor(Math.random() * (max - min + 1));

export async function politeWait() {
  await sleep(jitter(1200, 2600));
}

export async function smoothScroll(page, steps = 8) {
  for (let i = 0; i < steps; i++) {
    await page.mouse.wheel(0, jitter(300, 700));
    await sleep(jitter(600, 1400));
  }
}

export async function isCaptcha(page) {
  try {
    const url = page.url();
    return url.includes('showcaptcha') || url.includes('yandex.com/captcha');
  } catch {
    return false;
  }
}
