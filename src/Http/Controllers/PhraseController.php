<?php

namespace RepositorioMaster\TranslationsUI\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Inertia\Inertia;
use Inertia\Response;
use RepositorioMaster\TranslationsUI\Http\Resources\PhraseResource;
use RepositorioMaster\TranslationsUI\Http\Resources\TranslationFileResource;
use RepositorioMaster\TranslationsUI\Http\Resources\TranslationResource;
use RepositorioMaster\TranslationsUI\Models\Phrase;
use RepositorioMaster\TranslationsUI\Models\Translation;
use RepositorioMaster\TranslationsUI\Models\TranslationFile;
use Stichoza\GoogleTranslate\Exceptions\LargeTextException;
use Stichoza\GoogleTranslate\Exceptions\RateLimitException;
use Stichoza\GoogleTranslate\Exceptions\TranslationRequestException;
use Stichoza\GoogleTranslate\GoogleTranslate;

class PhraseController extends BaseController
{
    public function index(Translation $translation, Request $request): Response|RedirectResponse
    {
        if ($translation->source) {
            return redirect()->route('ltu.source_translation');
        }

        $phrases = $translation->phrases()->newQuery();

        $files = [];
        foreach (collect($phrases->where('translation_id', $translation->id)->get())->unique('translation_file_id') as $value) {
            $files[] = TranslationFile::where('id', $value->translation_file_id)->first();
        }

        if ($request->has('filter.keyword')) {
            $phrases->where(function (Builder $query) use ($request) {
                $query->where('key', 'LIKE', "%{$request->input('filter.keyword')}%")
                    ->orWhere('value', 'LIKE', "%{$request->input('filter.keyword')}%");
            });
        }

        if ($request->has('filter.translationFile')) {
            $phrases->where(
                ! is_null($request->input('filter.translationFile')) || ! empty($request->input('filter.translationFile'))
                    ? fn(Builder $query) => $query->where('translation_file_id', $request->input('filter.translationFile'))
                    : fn(Builder $query) => $query->whereNull('translation_file_id')
            );
        }

        if ($request->has('filter.status')) {
            $phrases->where(
                $request->input('filter.status') === 'translated'
                    ? fn(Builder $query) => $query->whereNotNull('value')
                    : fn(Builder $query) => $query->whereNull('value')
            );
        }

        $phrases = $phrases
            ->orderBy('key')
            ->paginate($request->input('perPage') ?? 12)
            ->withQueryString();

        return Inertia::render('phrases/index', [
            'phrases' => PhraseResource::collection($phrases),
            'translation' => TranslationResource::make($translation),
            'files' => TranslationFileResource::collection(collect($files)),
            'filter' => $request->input('filter', collect()),
        ]);
    }

    /**
     * @throws LargeTextException
     * @throws RateLimitException
     * @throws TranslationRequestException
     */
    public function edit(Translation $translation, Phrase $phrase): RedirectResponse|Response
    {
        if ($phrase->translation->source) {
            return redirect()->route('ltu.source_translation.edit', $phrase->uuid);
        }

        //Fix Import null source
        if ($phrase->phrase_id === null) {
            $phrase->phrase_id = Phrase::where('group', config('translations.source_language'))->where('key', $phrase->key)->first()->id;
            $phrase->save();
            $phrase->refresh();
        }

        return Inertia::render('phrases/edit', [
            'phrase' => PhraseResource::make($phrase),
            'translation' => TranslationResource::make($translation),
            'source' => TranslationResource::make(Translation::where('source', true)?->first()),
            'similarPhrases' => PhraseResource::collection($phrase->similarPhrases()),
            'suggestedTranslations' => [
                'google' => [
                    'id' => 'google',
                    'engine' => 'Google Translate',
                    'value' => (new GoogleTranslate)->preserveParameters()
                        ->setSource($phrase->source->translation->language->code)
                        ->setTarget($translation->language->code)
                        ->translate($phrase->source->value),
                ],
            ],
        ]);
    }

    public function update(Translation $translation, Phrase $phrase, Request $request): RedirectResponse
    {
        $request->validate([
            'phrase' => 'required|string',
        ]);

        if (! $translation->source) {
            if (is_array($phrase->source->parameters)) {
                foreach ($phrase->source->parameters as $parameter) {
                    if (! str_contains($request->input('phrase'), ":$parameter")) {
                        return redirect()->back()->withErrors([
                            'phrase' => 'Required parameters are missing.',
                        ]);
                    }
                }
            }
        }

        $phrase->update([
            'value' => $request->input('phrase'),
        ]);

        $nextPhrase = $translation->phrases()
            ->where('id', '>', $phrase->id)
            ->whereNull('value')
            ->first();

        if ($nextPhrase) {
            return redirect()->route('ltu.phrases.edit', [
                'translation' => $translation,
                'phrase' => $nextPhrase,
            ])->with('notification', [
                'type' => 'success',
                'body' => 'Phrase has been updated successfully',
            ]);
        }

        return redirect()->route('ltu.phrases.index', $translation)->with('notification', [
            'type' => 'success',
            'body' => 'Phrase has been updated successfully',
        ]);
    }
}
