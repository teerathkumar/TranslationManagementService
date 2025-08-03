<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\TranslationTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationTest extends TestCase
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

    public function test_can_list_translations(): void
    {
        Translation::factory()->count(5)->create();

        $response = $this->getJson('/api/translations');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ]
                ]);
    }

    public function test_can_create_translation(): void
    {
        $data = [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
            'namespace' => 'general',
            'is_active' => true,
            'tags' => ['test-tag'],
        ];

        $response = $this->postJson('/api/translations', $data);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'id',
                        'key',
                        'locale',
                        'content',
                        'namespace',
                        'is_active',
                        'tags',
                    ]
                ]);

        $this->assertDatabaseHas('translations', [
            'key' => 'test.key',
            'locale' => 'en',
            'content' => 'Test content',
        ]);
    }

    public function test_can_show_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'key',
                        'locale',
                        'content',
                        'namespace',
                        'is_active',
                        'tags',
                    ]
                ]);
    }

    public function test_can_update_translation(): void
    {
        $translation = Translation::factory()->create();
        $updateData = [
            'content' => 'Updated content',
            'is_active' => false,
        ];

        $response = $this->putJson("/api/translations/{$translation->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'data'
                ]);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'content' => 'Updated content',
            'is_active' => false,
        ]);
    }

    public function test_can_delete_translation(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->deleteJson("/api/translations/{$translation->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('translations', ['id' => $translation->id]);
    }

    public function test_can_search_translations(): void
    {
        Translation::factory()->create(['key' => 'searchable.key', 'content' => 'Searchable content']);
        Translation::factory()->create(['key' => 'other.key', 'content' => 'Other content']);

        $response = $this->getJson('/api/translations?search=searchable');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_locale(): void
    {
        Translation::factory()->create(['locale' => 'en']);
        Translation::factory()->create(['locale' => 'fr']);

        $response = $this->getJson('/api/translations?locale=en');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_namespace(): void
    {
        Translation::factory()->create(['namespace' => 'general']);
        Translation::factory()->create(['namespace' => 'admin']);

        $response = $this->getJson('/api/translations?namespace=general');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_tags(): void
    {
        $translation = Translation::factory()->create();
        $translation->tags()->attach($this->tag);

        $response = $this->getJson('/api/translations?tags=test-tag');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_export_translations(): void
    {
        Translation::factory()->create([
            'locale' => 'en',
            'namespace' => 'general',
            'key' => 'test.key',
            'content' => 'Test content',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/translations/export?locale=en&namespace=general');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'locale',
                    'namespace',
                    'translations',
                    'count',
                    'exported_at',
                ]);

        $this->assertArrayHasKey('test.key', $response->json('translations'));
    }

    public function test_export_respects_active_filter(): void
    {
        Translation::factory()->create([
            'locale' => 'en',
            'namespace' => 'general',
            'key' => 'active.key',
            'content' => 'Active content',
            'is_active' => true,
        ]);

        Translation::factory()->create([
            'locale' => 'en',
            'namespace' => 'general',
            'key' => 'inactive.key',
            'content' => 'Inactive content',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/translations/export?locale=en&namespace=general');

        $response->assertStatus(200);
        $this->assertArrayHasKey('active.key', $response->json('translations'));
        $this->assertArrayNotHasKey('inactive.key', $response->json('translations'));
    }

    public function test_export_with_tag_filter(): void
    {
        $translation = Translation::factory()->create([
            'locale' => 'en',
            'namespace' => 'general',
            'key' => 'tagged.key',
            'content' => 'Tagged content',
            'is_active' => true,
        ]);
        $translation->tags()->attach($this->tag);

        Translation::factory()->create([
            'locale' => 'en',
            'namespace' => 'general',
            'key' => 'untagged.key',
            'content' => 'Untagged content',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/translations/export?locale=en&namespace=general&tags[]=test-tag');

        $response->assertStatus(200);
        $this->assertArrayHasKey('tagged.key', $response->json('translations'));
        $this->assertArrayNotHasKey('untagged.key', $response->json('translations'));
    }

    public function test_validation_errors_on_create(): void
    {
        $response = $this->postJson('/api/translations', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['key', 'locale', 'content']);
    }

    public function test_validation_errors_on_update(): void
    {
        $translation = Translation::factory()->create();

        $response = $this->putJson("/api/translations/{$translation->id}", [
            'key' => '', // Invalid empty key
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['key']);
    }

    public function test_returns_404_for_nonexistent_translation(): void
    {
        $response = $this->getJson('/api/translations/999999');

        $response->assertStatus(404);
    }

    public function test_can_get_tags(): void
    {
        $response = $this->getJson('/api/tags');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                        ]
                    ]
                ]);
    }
}
