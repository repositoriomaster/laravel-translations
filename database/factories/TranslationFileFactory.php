<?php

namespace RepositorioMaster\TranslationsUI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RepositorioMaster\TranslationsUI\Models\TranslationFile;

class TranslationFileFactory extends Factory
{
    protected $model = TranslationFile::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['app', 'auth', 'pagination', 'passwords', 'validation']),
            'extension' => 'php',
            'is_root' => false,
        ];
    }

    public function json(): self
    {
        return $this->state([
            'extension' => 'json',
        ]);
    }
}
