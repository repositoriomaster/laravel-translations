<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RepositorioMaster\TranslationsUI\Enums\StatusEnum;
use RepositorioMaster\TranslationsUI\Models\Phrase;
use RepositorioMaster\TranslationsUI\Models\Translation;
use RepositorioMaster\TranslationsUI\Models\TranslationFile;

return new class extends Migration
{
    public function getConnection()
    {
        $connection = config('translations.database_connection');

        return $connection ?? $this->connection;
    }

    public function up(): void
    {
        Schema::create('ltu_phrases', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36);
            $table->foreignIdFor(Translation::class)->constrained('ltu_translations')->cascadeOnDelete();
            $table->foreignIdFor(TranslationFile::class)->constrained('ltu_translation_files')->cascadeOnDelete();
            $table->foreignIdFor(Phrase::class)->nullable()->constrained('ltu_phrases')->cascadeOnDelete();
            //Changed from string to text
            $table->text('key');
            $table->string('group');
            $table->text('value')->nullable();
            $table->string('status')->default(StatusEnum::active->value);
            $table->json('parameters')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ltu_phrases');
    }
};
