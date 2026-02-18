import { politeWait, sleep, jitter } from "./utils.js";

export async function parsePlaceSummary(page) {
  try {
    let title = null;
    let ratingValue = null;
    let ratingCount = null;

    await politeWait();

    try {
      title = await page.locator('h1.card-title-view__title').first().innerText().catch(() => null);
      if (title) {
        title = title.trim() || null;
      }
      if (!title) {
        title = await page.locator('[itemprop="name"]').first().innerText().catch(() => null);
        if (title) title = title.trim() || null;
      }
    } catch (e) {
    }

    try {
      const ariaLabels = await page.locator('[aria-label*="Оценка"]').all();

      for (const element of ariaLabels) {
        const ariaLabel = await element.getAttribute('aria-label').catch(() => null);
        if (ariaLabel) {
          const match = ariaLabel.match(/Оценка\s+([\d,]+)/);
          if (match) {
            ratingValue = parseFloat(match[1].replace(',', '.')) || null;
            break;
          }
        }
      }
    } catch (e) {
      console.log(`Method 1 failed: ${e.message}`);
    }

    if (!ratingValue) {
      try {
        const ratingTextElements = await page.locator('.business-summary-rating-badge-view__rating-text').all();
        if (ratingTextElements.length >= 2) {
          const parts = [];
          for (const element of ratingTextElements) {
            const text = await element.innerText().catch(() => null);
            if (text) {
              parts.push(text.trim());
            }
          }
          if (parts.length >= 2) {
            const ratingStr = parts.join('').replace(',', '.');
            const parsed = parseFloat(ratingStr);
            if (parsed && parsed >= 1 && parsed <= 5) {
              ratingValue = parsed;
              console.log(`Parsed rating from text elements: ${ratingValue} (parts: ${parts.join(', ')})`);
            }
          }
        }

        if (!ratingValue) {
          const ratingSelectors = [
            '.business-summary-rating-badge-view__rating',
            '.business-rating-badge-view',
            '[class*="rating"]',
          ];

          for (const selector of ratingSelectors) {
            const elements = await page.locator(selector).all();

            for (const element of elements) {
              const text = await element.innerText().catch(() => null);
              if (text) {
                const match = text.match(/([\d,]+)/);
                if (match) {
                  const parsed = parseFloat(match[1].replace(',', '.'));
                  if (parsed && parsed >= 1 && parsed <= 5) {
                    ratingValue = parsed;
                    break;
                  }
                }
              }
            }
            if (ratingValue) break;
          }
        }
      } catch (e) {
      }
    }

    if (!ratingValue) {
      try {
        const pageText = await page.textContent('body').catch(() => null);
        if (pageText) {
          const matches = pageText.match(/([1-5][,\.]\d+)/g);
          if (matches) {
            for (const match of matches) {
              const parsed = parseFloat(match.replace(',', '.'));
              if (parsed >= 1 && parsed <= 5) {
                ratingValue = parsed;
                break;
              }
            }
          }
        }
      } catch (e) {
      }
    }

    try {
      const countSelectors = [
        '.business-rating-amount-view._summary',
        '.business-rating-amount-view',
        '[class*="rating-amount"]',
      ];

      for (const selector of countSelectors) {
        const countText = await page.locator(selector).first().innerText().catch(() => null);
        if (countText) {
          const match = countText.match(/(\d+)/);
          if (match) {
            ratingCount = parseInt(match[1], 10) || null;
            break;
          }
        }
      }

      if (!ratingCount) {
        const pageText = await page.textContent('body').catch(() => null);
        if (pageText) {
          const match = pageText.match(/(\d+)\s+оценок?/i);
          if (match) {
            ratingCount = parseInt(match[1], 10) || null;
          }
        }
      }
    } catch (e) {
    }

    return {
      title: title,
      rating_value: ratingValue,
      rating_count: ratingCount,
    };
  } catch (e) {
    return {
      title: null,
      rating_value: null,
      rating_count: null,
    };
  }
}

export async function parseReviews(page, max = 50) {
  const reviewCard = ".business-review-view";

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
        console.log(`Found reviews using selector: ${selector}`);
        found = true;
        break;
      } catch (err) {
        // Продолжаем поиск
      }
    }

    if (!found) {
      await sleep(3000);
      const bodyText = await page.textContent('body').catch(() => '');
      if (!bodyText || bodyText.length < 100) {
        throw new Error('Page content not loaded properly');
      }
      console.log('Page loaded, but review selector not found. Continuing anyway...');
    }
  }

  // Улучшенный скроллинг для загрузки всех отзывов
  let prevCount = 0;
  let stableCount = 0; // Счетчик стабильных итераций

  for (let i = 0; i < 50; i++) {
    const count = await page.locator(reviewCard).count();

    if (count === 0 && i === 0) {
      console.log('No review cards found, skipping scroll (possibly CAPTCHA page)');
      break;
    }

    if (count === prevCount) {
      stableCount++;
      if (stableCount >= 3) break;
    } else {
      stableCount = 0;
    }

    if (count >= max) break;
    prevCount = count;

    if (count > 0) {
      try {
        const lastCard = page.locator(reviewCard).last();
        await lastCard.scrollIntoViewIfNeeded({ timeout: 5000 });
        await politeWait();
      } catch (e) {
        console.log('Scroll to last card failed:', e.message);
      }
    }

    await page.mouse.wheel(0, jitter(400, 900));
    await sleep(jitter(600, 1400));
  }

  // Проверяем наличие карточек отзывов
  const cards = page.locator(reviewCard);
  const count = await cards.count();

  if (count === 0) {
    console.log(`No review cards found with selector: ${reviewCard}`);
    // Пробуем альтернативные селекторы
    const alternativeSelectors = [
      '[class*="review"]',
      '[class*="Review"]',
      '.review-item',
      '[itemprop="review"]',
    ];

    for (const altSelector of alternativeSelectors) {
      const altCards = page.locator(altSelector);
      const altCount = await altCards.count();
      if (altCount > 0) {
        console.log(`Found ${altCount} reviews with alternative selector: ${altSelector}`);
        return []; // Структура может отличаться, используем основной селектор
      }
    }

    console.log('No reviews found on page');
    return [];
  }

  const finalCount = Math.min(count, max);
  console.log(`Found ${finalCount} review cards (out of ${count} total)`);

  const results = [];
  for (let i = 0; i < count; i++) {
    try {
      const card = cards.nth(i);

      const userProfileUrl = await card.locator("a.business-review-view__user-icon").getAttribute("href").catch(() => null);
      const userUid = userProfileUrl?.split("/maps/user/")?.[1]?.split("?")[0]?.replaceAll("/", "") || null;

      // Альтернативные селекторы для user_uid если основной не работает
      let finalUserUid = userUid;
      if (!finalUserUid) {
        // Пробуем извлечь из других мест
        const altUrl = await card.locator("a[href*='/maps/user/']").first().getAttribute("href").catch(() => null);
        if (altUrl) {
          finalUserUid = altUrl.split("/maps/user/")?.[1]?.split("?")[0]?.split("/")[0] || null;
        }
      }

      let userName = await card.locator('[itemprop="author"] [itemprop="name"]').innerText().catch(() => null);

      // Альтернативный селектор для имени
      if (!userName) {
        const altName = await card.locator(".business-review-view__author-name").innerText().catch(() => null);
        if (altName) {
          userName = altName.trim();
        }
      }

      // ratingValue лежит в meta
      let ratingValue = await card.locator('[itemprop="reviewRating"] meta[itemprop="ratingValue"]').getAttribute("content").catch(() => null);

      // Альтернативный способ получения рейтинга
      if (!ratingValue) {
        const ratingStars = await card.locator('[itemprop="reviewRating"]').getAttribute("content").catch(() => null);
        if (ratingStars) {
          ratingValue = ratingStars;
        }
      }

      let publishedAt = await card.locator('meta[itemprop="datePublished"]').getAttribute("content").catch(() => null);

      // Альтернативный селектор для даты
      if (!publishedAt) {
        const dateText = await card.locator(".business-review-view__date").getAttribute("datetime").catch(() => null);
        if (dateText) {
          publishedAt = dateText;
        }
      }

      let body = await card.locator('[itemprop="reviewBody"]').innerText().catch(() => null);

      // Альтернативный селектор для текста отзыва
      if (!body) {
        const altBody = await card.locator(".business-review-view__text").innerText().catch(() => null);
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
        body: body?.trim() || null
      });
    } catch (e) {
      console.error(`Error parsing review ${i}:`, e.message);
      // Продолжаем обработку остальных отзывов
    }
  }

  console.log(`Parsed ${results.length} reviews, ${results.filter(r => r.user_uid).length} with user_uid`);
  return results;
}
