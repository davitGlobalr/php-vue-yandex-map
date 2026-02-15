<?php

namespace Tests\Unit;

use App\Jobs\ImportReviewsJob;
use App\Models\Place;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ImportReviewsJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Устанавливаем тестовый путь для данных
        config(['services.import_data_path' => storage_path('app/testing/import-data')]);

        // Создаем директорию если её нет
        if (!is_dir(config('services.import_data_path'))) {
            mkdir(config('services.import_data_path'), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Очищаем тестовые файлы
        $testDir = config('services.import_data_path');
        if (is_dir($testDir)) {
            array_map('unlink', glob("$testDir/*.jsonl"));
        }

        parent::tearDown();
    }

    public function test_import_reviews_creates_place_if_not_exists(): void
    {
        $placeId = '215796158208';
        $filePath = "{$placeId}.jsonl";
        $fullPath = config('services.import_data_path') . '/' . $filePath;

        // Создаем тестовый JSONL файл
        $testData = [
            [
                'place_id' => $placeId,
                'user_uid' => 'user123',
                'user_name' => 'Test User',
                'rating' => 5,
                'body' => 'Great place!',
                'published_at' => '2024-01-15T10:30:00Z',
                'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            ],
        ];

        file_put_contents($fullPath, implode("\n", array_map('json_encode', $testData)));

        // Запускаем job
        $job = new ImportReviewsJob($placeId, $filePath);
        $job->handle();

        // Проверяем что Place создан
        $place = Place::where('source_org_id', $placeId)->first();
        $this->assertNotNull($place);
        $this->assertEquals('https://yandex.com/maps/org/grill_am/215796158208/reviews', $place->source_url);
    }

    public function test_import_reviews_creates_reviews(): void
    {
        $placeId = '215796158208';
        $filePath = "{$placeId}.jsonl";
        $fullPath = config('services.import_data_path') . '/' . $filePath;

        // Создаем Place
        $place = Place::create([
            'source_org_id' => (int) $placeId,
            'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            'title' => 'Grill.am',
        ]);

        // Создаем тестовый JSONL файл
        $testData = [
            [
                'place_id' => $placeId,
                'user_uid' => 'user123',
                'user_name' => 'Test User 1',
                'rating' => 5,
                'body' => 'Great place!',
                'published_at' => '2024-01-15T10:30:00Z',
                'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            ],
            [
                'place_id' => $placeId,
                'user_uid' => 'user456',
                'user_name' => 'Test User 2',
                'rating' => 4,
                'body' => 'Good food',
                'published_at' => '2024-01-16T11:00:00Z',
                'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            ],
        ];

        file_put_contents($fullPath, implode("\n", array_map('json_encode', $testData)));

        // Запускаем job
        $job = new ImportReviewsJob($placeId, $filePath);
        $job->handle();

        // Проверяем что отзывы созданы
        $reviews = DB::table('place_reviews')->where('place_id', $place->id)->get();
        $this->assertCount(2, $reviews);

        $this->assertEquals('user123', $reviews[0]->source_user_uid);
        $this->assertEquals('Test User 1', $reviews[0]->user_name);
        $this->assertEquals(5, $reviews[0]->rating);
        $this->assertEquals('Great place!', $reviews[0]->review);
    }

    public function test_import_reviews_skips_invalid_data(): void
    {
        $placeId = '215796158208';
        $filePath = "{$placeId}.jsonl";
        $fullPath = config('services.import_data_path') . '/' . $filePath;

        $place = Place::create([
            'source_org_id' => (int) $placeId,
            'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
        ]);

        // Создаем тестовый JSONL файл с невалидными данными
        $testData = [
            ['error' => 'Some error'], // ошибка
            ['place_id' => $placeId], // нет user_uid
            [
                'place_id' => $placeId,
                'user_uid' => 'user123',
                'user_name' => 'Test User',
                'rating' => 10, // невалидный рейтинг
                'body' => 'Test',
            ],
            [
                'place_id' => $placeId,
                'user_uid' => 'user456',
                'user_name' => 'Valid User',
                'rating' => 5,
                'body' => 'Valid review',
                'published_at' => '2024-01-15T10:30:00Z',
                'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            ],
        ];

        file_put_contents($fullPath, implode("\n", array_map('json_encode', $testData)));

        // Запускаем job
        $job = new ImportReviewsJob($placeId, $filePath);
        $job->handle();

        // Проверяем что создан только один валидный отзыв
        $reviews = DB::table('place_reviews')->where('place_id', $place->id)->get();
        $this->assertCount(1, $reviews);
        $this->assertEquals('user456', $reviews[0]->source_user_uid);
    }

    public function test_import_reviews_handles_missing_file(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Import file not found: ' . config('services.import_data_path') . '/nonexistent.jsonl');

        $job = new ImportReviewsJob('123', 'nonexistent.jsonl');
        $job->handle();

        // Проверяем что ничего не создано
        $this->assertEquals(0, Place::count());
        $this->assertEquals(0, DB::table('place_reviews')->count());
    }

    public function test_import_reviews_prevents_duplicates(): void
    {
        $placeId = '215796158208';
        $filePath = "{$placeId}.jsonl";
        $fullPath = config('services.import_data_path') . '/' . $filePath;

        $place = Place::create([
            'source_org_id' => (int) $placeId,
            'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
        ]);

        // Создаем существующий отзыв
        DB::table('place_reviews')->insert([
            'place_id' => $place->id,
            'source_user_uid' => 'user123',
            'user_name' => 'Existing User',
            'rating' => 5,
            'review' => 'Existing review',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Создаем JSONL файл с тем же user_uid
        $testData = [
            [
                'place_id' => $placeId,
                'user_uid' => 'user123',
                'user_name' => 'Updated User',
                'rating' => 4,
                'body' => 'Updated review',
                'published_at' => '2024-01-15T10:30:00Z',
                'source_url' => 'https://yandex.com/maps/org/grill_am/215796158208/reviews',
            ],
        ];

        file_put_contents($fullPath, implode("\n", array_map('json_encode', $testData)));

        // Запускаем job
        $job = new ImportReviewsJob($placeId, $filePath);
        $job->handle();

        // Проверяем что отзыв не дублировался
        $reviews = DB::table('place_reviews')->where('place_id', $place->id)->get();
        $this->assertCount(1, $reviews);
        $this->assertEquals('Existing User', $reviews[0]->user_name); // Старое значение сохранилось
    }
}
