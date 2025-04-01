<?php

namespace RepositorioMaster\TranslationsUI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RepositorioMaster\TranslationsUI\Models\Language;
use RepositorioMaster\TranslationsUI\Models\Translation;

class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    public function definition(): array
    {
        return [
            'source' => false,
            'language_id' => Language::factory(),
        ];
    }

    public function source(): self
    {
        return $this->state([
            'source' => true,
        ]);
    }
}
