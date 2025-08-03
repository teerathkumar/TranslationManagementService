<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use App\Models\TranslationTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TranslationController extends Controller
{
    /**
     * Display a listing of the resource with search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Translation::with('tags');

        // Apply filters
        if ($request->has('locale')) {
            $query->byLocale($request->locale);
        }

        if ($request->has('namespace')) {
            $query->byNamespace($request->namespace);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('tags')) {
            $query->byTags(explode(',', $request->tags));
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $translations = $query->paginate($perPage);

        return response()->json([
            'data' => $translations->items(),
            'meta' => [
                'current_page' => $translations->currentPage(),
                'last_page' => $translations->lastPage(),
                'per_page' => $translations->perPage(),
                'total' => $translations->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255|unique:translations,key',
            'locale' => 'required|string|max:10',
            'content' => 'required|string',
            'namespace' => 'string|max:255',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'tags' => 'array',
            'tags.*' => 'string|exists:translation_tags,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $translation = Translation::create($request->only([
                'key', 'locale', 'content', 'namespace', 'is_active', 'metadata'
            ]));

            if ($request->has('tags')) {
                $tagIds = TranslationTag::whereIn('name', $request->tags)->pluck('id');
                $translation->tags()->attach($tagIds);
            }

            DB::commit();

            // Clear cache
            Cache::forget("translations_{$translation->locale}_{$translation->namespace}");

            return response()->json([
                'message' => 'Translation created successfully',
                'data' => $translation->load('tags')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create translation'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $translation = Translation::with('tags')->find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }

        return response()->json(['data' => $translation]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $translation = Translation::find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => ['string', 'max:255', Rule::unique('translations')->ignore($id)],
            'locale' => 'string|max:10',
            'content' => 'string',
            'namespace' => 'string|max:255',
            'is_active' => 'boolean',
            'metadata' => 'array',
            'tags' => 'array',
            'tags.*' => 'string|exists:translation_tags,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $translation->update($request->only([
                'key', 'locale', 'content', 'namespace', 'is_active', 'metadata'
            ]));

            if ($request->has('tags')) {
                $tagIds = TranslationTag::whereIn('name', $request->tags)->pluck('id');
                $translation->tags()->sync($tagIds);
            }

            DB::commit();

            // Clear cache
            Cache::forget("translations_{$translation->locale}_{$translation->namespace}");

            return response()->json([
                'message' => 'Translation updated successfully',
                'data' => $translation->load('tags')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update translation'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $translation = Translation::find($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Clear cache before deletion
            Cache::forget("translations_{$translation->locale}_{$translation->namespace}");
            
            $translation->delete();
            DB::commit();

            return response()->json(['message' => 'Translation deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete translation'], 500);
        }
    }

    /**
     * Export translations for frontend use with optimized performance.
     */
    public function export(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'locale' => 'required|string|max:10',
            'namespace' => 'string|max:255',
            'tags' => 'array',
            'tags.*' => 'string|exists:translation_tags,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $locale = $request->locale;
        $namespace = $request->get('namespace', 'general');
        $tags = $request->get('tags', []);

        // Create cache key
        $cacheKey = "translations_export_{$locale}_{$namespace}_" . md5(serialize($tags));

        // Try to get from cache first
        $translations = Cache::remember($cacheKey, 3600, function () use ($locale, $namespace, $tags) {
            $query = Translation::where('locale', $locale)
                ->where('namespace', $namespace)
                ->where('is_active', true);

            if (!empty($tags)) {
                $query->byTags($tags);
            }

            return $query->get(['key', 'content'])->pluck('content', 'key')->toArray();
        });

        return response()->json([
            'locale' => $locale,
            'namespace' => $namespace,
            'translations' => $translations,
            'count' => count($translations),
            'exported_at' => now()->toISOString(),
        ]);
    }
}
