<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\TranslationTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private TranslationTag $tag;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->tag = TranslationTag::create(['name' => 'test-tag', 'description' => 'Test tag']);
        
        Sanctum::actingAs($this->user);
    }

    public function test_export_endpoint_performance_with_large_dataset(): void
    {
        // Generate 100,000 translations for performance testing
        $this->artisan('translations:generate', ['count' => 100000, '--batch' => 1000]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations/export?locale=en&namespace=general');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 500ms as required
        $this->assertLessThan(500, $responseTime, 
            "Export endpoint took {$responseTime}ms, expected under 500ms");
        
        $this->info("Export endpoint response time: {$responseTime}ms");
    }

    public function test_list_endpoint_performance(): void
    {
        // Generate 10,000 translations for list performance testing
        $this->artisan('translations:generate', ['count' => 10000, '--batch' => 1000]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations?per_page=100');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "List endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("List endpoint response time: {$responseTime}ms");
    }

    public function test_search_performance(): void
    {
        // Generate 50,000 translations for search performance testing
        $this->artisan('translations:generate', ['count' => 50000, '--batch' => 1000]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations?search=welcome&per_page=50');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "Search endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("Search endpoint response time: {$responseTime}ms");
    }

    public function test_filter_performance(): void
    {
        // Generate 25,000 translations for filter performance testing
        $this->artisan('translations:generate', ['count' => 25000, '--batch' => 1000]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations?locale=en&namespace=general&tags=test-tag&per_page=50');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "Filter endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("Filter endpoint response time: {$responseTime}ms");
    }

    public function test_create_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->postJson('/api/translations', [
            'key' => 'performance.test.key',
            'locale' => 'en',
            'content' => 'Performance test content',
            'namespace' => 'general',
            'is_active' => true,
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(201);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "Create endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("Create endpoint response time: {$responseTime}ms");
    }

    public function test_update_performance(): void
    {
        $translation = Translation::factory()->create();

        $startTime = microtime(true);

        $response = $this->putJson("/api/translations/{$translation->id}", [
            'content' => 'Updated performance test content',
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "Update endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("Update endpoint response time: {$responseTime}ms");
    }

    public function test_delete_performance(): void
    {
        $translation = Translation::factory()->create();

        $startTime = microtime(true);

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 200ms as required
        $this->assertLessThan(200, $responseTime, 
            "Delete endpoint took {$responseTime}ms, expected under 200ms");
        
        $this->info("Delete endpoint response time: {$responseTime}ms");
    }

    public function test_export_cache_performance(): void
    {
        // Generate some translations
        $this->artisan('translations:generate', ['count' => 10000, '--batch' => 1000]);

        // First request (cache miss)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/translations/export?locale=en&namespace=general');
        $endTime = microtime(true);
        $firstResponseTime = ($endTime - $startTime) * 1000;

        // Second request (cache hit)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/translations/export?locale=en&namespace=general');
        $endTime = microtime(true);
        $secondResponseTime = ($endTime - $startTime) * 1000;

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Cache hit should be significantly faster
        $this->assertLessThan($firstResponseTime * 0.5, $secondResponseTime, 
            "Cached response should be at least 50% faster than uncached");
        
        $this->info("First request (cache miss): {$firstResponseTime}ms");
        $this->info("Second request (cache hit): {$secondResponseTime}ms");
    }

    public function test_concurrent_export_requests(): void
    {
        // Generate translations
        $this->artisan('translations:generate', ['count' => 50000, '--batch' => 1000]);

        $startTime = microtime(true);

        // Simulate concurrent requests (in a real scenario, these would be parallel)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/translations/export?locale=en&namespace=general');
            $response->assertStatus(200);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 5;

        // Each request should still be under 500ms
        $this->assertLessThan(500, $averageTime, 
            "Average concurrent request time: {$averageTime}ms, expected under 500ms");
        
        $this->info("Average concurrent request time: {$averageTime}ms");
    }

    private function info(string $message): void
    {
        // Output performance metrics for monitoring
        echo "\n" . $message . "\n";
    }
}
