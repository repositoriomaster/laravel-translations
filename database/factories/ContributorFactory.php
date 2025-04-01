<?php

namespace RepositorioMaster\TranslationsUI\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RepositorioMaster\TranslationsUI\Models\Contributor;

class ContributorFactory extends Factory
{
    protected $model = Contributor::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'role' => $this->faker->randomElement(['admin', 'translator']),
            'password' => bcrypt('password'),
            'remember_token' => null,
        ];
    }
}
