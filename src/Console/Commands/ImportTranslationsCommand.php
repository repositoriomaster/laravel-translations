<?php

namespace RepositorioMaster\TranslationsUI\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RepositorioMaster\TranslationsUI\Actions\SyncPhrasesAction;
use RepositorioMaster\TranslationsUI\Database\Seeders\LanguagesTableSeeder;
use RepositorioMaster\TranslationsUI\Models\Language;
use RepositorioMaster\TranslationsUI\Models\Phrase;
use RepositorioMaster\TranslationsUI\Models\Translation;
use RepositorioMaster\TranslationsUI\Models\TranslationFile;
use RepositorioMaster\TranslationsUI\TranslationsManager;

class ImportTranslationsCommand extends Command
{
    public TranslationsManager $manager;

    private bool $overwrite = true;

    protected $signature = 'translations:import {--F|fresh : Truncate all translations and phrases before importing} {--no-overwrite : Do not overwrite existing translations}';

    protected $description = 'Sync translation all keys from the translation files to the database';

    public function __construct(TranslationsManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    public function handle(): void
    {
        $this->importLanguages();

        if ($this->option('fresh') && $this->confirm('Are you sure you want to truncate all translations and phrases?')) {
            $this->info('Truncating translations and phrases...' . PHP_EOL);

            $this->truncateTables();
        }

        if ($this->option('no-overwrite')) {
            $this->overwrite = false;
        }

        $translation = $this->createOrGetSourceLanguage();

        $this->info('Importing translations...' . PHP_EOL);

        $this->withProgressBar($this->manager->getLocales(), function ($locale) use ($translation) {
            $this->syncTranslations($translation, $locale);
        });

        $this->info('Fix transalations without source...' . PHP_EOL);

        //Fix transalations without source
        $sourcePhrases = Phrase::where('group', config('translations.source_language'))->get();
        $phrases = Phrase::where('group', '!=', config('translations.source_language'))->where('phrase_id', null)->get();
        foreach ($phrases as $phrase) {
            $phrase_id = $sourcePhrases->where('group', config('translations.source_language'))->where('key', $phrase->key)->first();

            if ($phrase_id != null) {
                $phrase->phrase_id = $phrase_id->id;
                $phrase->save();
            }
        }
    }

    protected function importLanguages(): void
    {
        if (! $this->getSchema()->hasTable('ltu_languages') || Language::count() === 0) {
            if ($this->confirm('The ltu_languages table does not exist or is empty, would you like to install the default languages?', true)) {
                $this->call('db:seed', ['--class' => LanguagesTableSeeder::class]);
            } else {
                $this->error('The ltu_languages table does not exist or is empty, please run the translations:install command first.');

                exit;
            }
        }
    }

    protected function truncateTables(): void
    {
        $this->getSchema()->withoutForeignKeyConstraints(function () {
            Phrase::truncate();
            Translation::truncate();
            TranslationFile::truncate();
        });
    }

    protected function getSchema(): SchemaBuilder
    {
        return Schema::connection(config('translations.database_connection'));
    }

    public function createOrGetSourceLanguage(): Translation
    {
        $language = Language::where('code', config('translations.source_language'))->first();

        if (! $language) {
            $this->error('Language with code ' . config('translations.source_language') . ' not found' . PHP_EOL);

            exit;
        }

        if (! is_dir(lang_path()) || count(scandir(lang_path())) <= 2) {
            if ($this->confirm('It seems that you don\'t have any languages yet, would you like to publish the default language files?', true)) {
                $this->call('lang:publish');
            } else {
                $this->error('We can\'t find any languages in your project, please run the lang:publish command first.');

                exit;
            }
        }

        $translation = Translation::firstOrCreate([
            'source' => true,
            'language_id' => $language->id,
        ]);


        $this->syncTranslations($translation, $language->code);

        return $translation;
    }

    public function syncTranslations(Translation $translation, string $locale): void
    {
        foreach ($this->manager->getTranslations($locale) as $file => $translations) {
            foreach (Arr::dot($translations) as $key => $value) {
                SyncPhrasesAction::execute($translation, $key, $value, $locale, $file, $this->overwrite);
            }
        }

        if ($locale === config('translations.source_language')) {
            return;
        }

        // syncMissingTranslations overwrite existing translations during import
        //$this->syncMissingTranslations($translation, $locale);
    }

    // public function syncMissingTranslations(Translation $source, string $locale): void
    // {
    //     $language = Language::where('code', $locale)->first();

    //     $translation = Translation::firstOrCreate([
    //         'language_id' => $language->id,
    //         'source' => false,
    //     ]);

    //     $source->load('phrases.translation', 'phrases.file');

    //     $source->phrases->each(function ($phrase) use ($translation, $locale) {
    //         if (! $translation->phrases()->where('key', $phrase->key)->where('group', $phrase->group)->first()) {
    //             $fileName = $phrase->file->name . '.' . $phrase->file->extension;

    //             if ($phrase->file->name === config('translations.source_language')) {
    //                 $fileName = Str::replaceStart(config('translations.source_language') . '.', "{$locale}.", $fileName);
    //             } else {
    //                 $fileName = Str::replaceStart(config('translations.source_language') . '/', "{$locale}/", $fileName);
    //             }

    //             SyncPhrasesAction::execute($phrase->translation, $phrase->key, '', $locale, $fileName);
    //         }
    //     });
    // }
}
