<?php

namespace App\Console\Commands;

use App\Models\Translation;
use App\Models\TranslationTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTranslationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:generate {count=100000 : Number of translations to generate} {--batch=1000 : Batch size for insertion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate dummy translation data for testing';

    private array $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh'];
    private array $namespaces = ['general', 'mobile', 'desktop', 'web', 'admin', 'user', 'error', 'success'];
    private array $tags = ['mobile', 'desktop', 'web', 'admin', 'user', 'error', 'success', 'notification', 'form', 'button'];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $batchSize = (int) $this->option('batch');

        $this->info("Generating {$count} translation records...");

        // Create tags if they don't exist
        $this->createTags();

        // Generate translations in batches
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $processed = 0;
        while ($processed < $count) {
            $currentBatch = min($batchSize, $count - $processed);
            $this->generateBatch($currentBatch);
            $processed += $currentBatch;
            $bar->advance($currentBatch);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully generated {$count} translation records!");
    }

    private function createTags(): void
    {
        foreach ($this->tags as $tagName) {
            TranslationTag::firstOrCreate(['name' => $tagName]);
        }
        $this->info('Tags created successfully.');
    }

    private function generateBatch(int $batchSize): void
    {
        $translations = [];
        $tagIds = TranslationTag::pluck('id')->toArray();

        for ($i = 0; $i < $batchSize; $i++) {
            $locale = $this->locales[array_rand($this->locales)];
            $namespace = $this->namespaces[array_rand($this->namespaces)];
            $key = $this->generateKey($namespace);
            $content = $this->generateContent($locale, $namespace);

            $translations[] = [
                'key' => $key,
                'locale' => $locale,
                'content' => $content,
                'namespace' => $namespace,
                'is_active' => rand(0, 10) > 1, // 90% active
                'metadata' => json_encode([
                    'generated_at' => now()->toISOString(),
                    'batch_id' => uniqid(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert translations
        DB::table('translations')->insert($translations);

        // Attach random tags to translations
        $this->attachTagsToBatch($translations, $tagIds);
    }

    private function generateKey(string $namespace): string
    {
        $prefixes = [
            'general' => ['welcome', 'hello', 'goodbye', 'thanks', 'sorry'],
            'mobile' => ['mobile.welcome', 'mobile.menu', 'mobile.button', 'mobile.error'],
            'desktop' => ['desktop.welcome', 'desktop.menu', 'desktop.button', 'desktop.error'],
            'web' => ['web.welcome', 'web.menu', 'web.button', 'web.error'],
            'admin' => ['admin.dashboard', 'admin.users', 'admin.settings', 'admin.reports'],
            'user' => ['user.profile', 'user.settings', 'user.preferences', 'user.account'],
            'error' => ['error.404', 'error.500', 'error.validation', 'error.permission'],
            'success' => ['success.saved', 'success.updated', 'success.deleted', 'success.created'],
        ];

        $prefix = $prefixes[$namespace][array_rand($prefixes[$namespace])];
        return $prefix . '.' . Str::random(8);
    }

    private function generateContent(string $locale, string $namespace): string
    {
        $contents = [
            'en' => [
                'general' => ['Welcome', 'Hello', 'Goodbye', 'Thank you', 'Sorry'],
                'mobile' => ['Mobile Welcome', 'Mobile Menu', 'Mobile Button', 'Mobile Error'],
                'desktop' => ['Desktop Welcome', 'Desktop Menu', 'Desktop Button', 'Desktop Error'],
                'web' => ['Web Welcome', 'Web Menu', 'Web Button', 'Web Error'],
                'admin' => ['Admin Dashboard', 'Admin Users', 'Admin Settings', 'Admin Reports'],
                'user' => ['User Profile', 'User Settings', 'User Preferences', 'User Account'],
                'error' => ['Page Not Found', 'Server Error', 'Validation Error', 'Permission Denied'],
                'success' => ['Successfully Saved', 'Successfully Updated', 'Successfully Deleted', 'Successfully Created'],
            ],
            'fr' => [
                'general' => ['Bienvenue', 'Bonjour', 'Au revoir', 'Merci', 'Désolé'],
                'mobile' => ['Bienvenue Mobile', 'Menu Mobile', 'Bouton Mobile', 'Erreur Mobile'],
                'desktop' => ['Bienvenue Desktop', 'Menu Desktop', 'Bouton Desktop', 'Erreur Desktop'],
                'web' => ['Bienvenue Web', 'Menu Web', 'Bouton Web', 'Erreur Web'],
                'admin' => ['Tableau de Bord Admin', 'Utilisateurs Admin', 'Paramètres Admin', 'Rapports Admin'],
                'user' => ['Profil Utilisateur', 'Paramètres Utilisateur', 'Préférences Utilisateur', 'Compte Utilisateur'],
                'error' => ['Page Non Trouvée', 'Erreur Serveur', 'Erreur Validation', 'Permission Refusée'],
                'success' => ['Sauvegardé avec Succès', 'Mis à Jour avec Succès', 'Supprimé avec Succès', 'Créé avec Succès'],
            ],
            'es' => [
                'general' => ['Bienvenido', 'Hola', 'Adiós', 'Gracias', 'Lo siento'],
                'mobile' => ['Bienvenido Móvil', 'Menú Móvil', 'Botón Móvil', 'Error Móvil'],
                'desktop' => ['Bienvenido Escritorio', 'Menú Escritorio', 'Botón Escritorio', 'Error Escritorio'],
                'web' => ['Bienvenido Web', 'Menú Web', 'Botón Web', 'Error Web'],
                'admin' => ['Panel Admin', 'Usuarios Admin', 'Configuración Admin', 'Reportes Admin'],
                'user' => ['Perfil Usuario', 'Configuración Usuario', 'Preferencias Usuario', 'Cuenta Usuario'],
                'error' => ['Página No Encontrada', 'Error Servidor', 'Error Validación', 'Permiso Denegado'],
                'success' => ['Guardado Exitosamente', 'Actualizado Exitosamente', 'Eliminado Exitosamente', 'Creado Exitosamente'],
            ],
        ];

        $localeContents = $contents[$locale] ?? $contents['en'];
        $namespaceContents = $localeContents[$namespace] ?? $localeContents['general'];
        
        return $namespaceContents[array_rand($namespaceContents)] . ' ' . Str::random(4);
    }

    private function attachTagsToBatch(array $translations, array $tagIds): void
    {
        $pivotData = [];
        
        foreach ($translations as $translation) {
            // Get 1-3 random tags for each translation
            $numTags = rand(1, 3);
            $selectedTags = array_rand($tagIds, $numTags);
            
            if (!is_array($selectedTags)) {
                $selectedTags = [$selectedTags];
            }
            
            foreach ($selectedTags as $tagIndex) {
                $pivotData[] = [
                    'translation_id' => DB::getPdo()->lastInsertId() - count($translations) + array_search($translation, $translations) + 1,
                    'translation_tag_id' => $tagIds[$tagIndex],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($pivotData)) {
            DB::table('translation_tag_pivot')->insert($pivotData);
        }
    }
}
