import fs from "fs";
import path from "path";

const DATA_DIR = process.env.DATA_DIR || "/app/data";
const PROFILE_DIR = path.join(DATA_DIR, "profile");
const SCREENSHOTS_DIR = path.join(DATA_DIR, "screenshots");

export function ensureDataDir() {
  fs.mkdirSync(DATA_DIR, { recursive: true });
  fs.mkdirSync(PROFILE_DIR, { recursive: true });
  fs.mkdirSync(SCREENSHOTS_DIR, { recursive: true });
}

export function getProfileDir() {
  return PROFILE_DIR;
}

export function getScreenshotsDir() {
  return SCREENSHOTS_DIR;
}

export function saveScreenshot(placeId, prefix = "captcha") {
  const dir = getScreenshotsDir();
  const filename = `${placeId}_${prefix}_${Date.now()}.png`;
  const filepath = path.join(dir, filename);
  return filepath;
}

export function filePathForPlace(placeId) {
  return path.join(DATA_DIR, `${placeId}.jsonl`);
}

export function appendJsonl(placeId, obj) {
  const p = filePathForPlace(placeId);
  fs.appendFileSync(p, JSON.stringify(obj) + "\n", "utf8");
}
