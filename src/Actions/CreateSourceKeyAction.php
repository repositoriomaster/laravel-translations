<?php

namespace RepositorioMaster\TranslationsUI\Actions;

use RepositorioMaster\TranslationsUI\Models\Translation;
use RepositorioMaster\TranslationsUI\Models\TranslationFile;

class CreateSourceKeyAction
{
    public static function execute(string $key, string $file, string $content): void
    {
        $sourceTranslation = Translation::where('source', true)->first();

        $sourceKey = $sourceTranslation->phrases()->create([
            'key' => $key,
            'phrase_id' => null,
            'value' => $content,
            'uuid' => str()->uuid(),
            'translation_file_id' => $file,
            'parameters' => getPhraseParameters($content),
            'group' => TranslationFile::find($file)?->name,
        ]);

        CopySourceKeyToTranslationsAction::execute($sourceKey);
    }
}
