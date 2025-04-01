<?php

namespace RepositorioMaster\TranslationsUI\Actions;

use RepositorioMaster\TranslationsUI\Models\Language;
use RepositorioMaster\TranslationsUI\Models\Translation;

class CreateTranslationForLanguageAction
{
    public static function execute(Language $language): void
    {
        $translation = Translation::create([
            'source' => false,
            'language_id' => $language->id,
        ]);

        CopyPhrasesFromSourceAction::execute($translation);
    }
}
