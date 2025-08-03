<?php

namespace Database\Factories;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Translation>
 */
class TranslationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh'];
        $namespaces = ['general', 'mobile', 'desktop', 'web', 'admin', 'user', 'error', 'success'];
        $keys = [
            'welcome', 'hello', 'goodbye', 'thanks', 'sorry',
            'button.save', 'button.cancel', 'button.delete', 'button.edit',
            'form.email', 'form.password', 'form.name', 'form.submit',
            'error.404', 'error.500', 'error.validation', 'error.permission',
            'success.saved', 'success.updated', 'success.deleted', 'success.created',
        ];

        return [
            'key' => $this->faker->randomElement($keys) . '.' . Str::random(8),
            'locale' => $this->faker->randomElement($locales),
            'content' => $this->faker->sentence(),
            'namespace' => $this->faker->randomElement($namespaces),
            'is_active' => $this->faker->boolean(90), // 90% active
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'factory' => true,
            ],
        ];
    }

    /**
     * Indicate that the translation is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the translation is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the locale for the translation.
     */
    public function locale(string $locale): static
    {
        return $this->state(fn (array $attributes) => [
            'locale' => $locale,
        ]);
    }

    /**
     * Set the namespace for the translation.
     */
    public function namespace(string $namespace): static
    {
        return $this->state(fn (array $attributes) => [
            'namespace' => $namespace,
        ]);
    }
}
