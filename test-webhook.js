#!/usr/bin/env node

/**
 * Тестовый скрипт для проверки webhook /api/parser/needs-manual
 *
 * Использование:
 *   node test-webhook.js <place_id> [reason] [screenshot_path]
 *
 * Пример:
 *   node test-webhook.js 12345 captcha screenshot.png
 */

const crypto = require('crypto');

// Параметры из аргументов командной строки
const placeId = process.argv[2] || '12345';
const reason = process.argv[3] || 'captcha';
const screenshotPath = process.argv[4] || 'test-captcha.png';

// Конфигурация (измените под вашу настройку)
const WEBHOOK_URL = process.env.WEBHOOK_URL || 'http://localhost:8078/api/parser/needs-manual';
const SECRET =
    process.env.PARSER_WEBHOOK_SECRET || '56bf6dd558911d8155d6404afe9313eb85f8d938fa1d488951e4919fdcb6a4f0';

// Тело запроса
const payload = {
  place_id: String(placeId),
  reason: reason,
  screenshot_path: screenshotPath,
};

const body = JSON.stringify(payload);

// Генерируем подпись
const signature = crypto
  .createHmac('sha256', SECRET)
  .update(body)
  .digest('hex');

console.log('📤 Отправка webhook запроса...');
console.log('URL:', WEBHOOK_URL);
console.log('Payload:', body);
console.log('Signature:', signature);
console.log('');

// Отправляем запрос
fetch(WEBHOOK_URL, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Parser-Signature': signature,
  },
  body: body,
})
  .then(async (res) => {
    const text = await res.text();
    console.log(`✅ Статус: ${res.status} ${res.statusText}`);
    console.log('Ответ:', text);

    if (!res.ok) {
      process.exit(1);
    }
  })
  .catch((error) => {
    console.error('❌ Ошибка:', error.message);
    process.exit(1);
  });
