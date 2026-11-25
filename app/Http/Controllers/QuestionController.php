<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Tag;
use App\Support\TagParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionController extends Controller
{

    public function create()
    {
        return view('questions.create');
    }
    public function show(Question $question, ?string $slug = null)
    {
        // '' (title with no sluggable characters) and a missing slug must count as equal,
        // otherwise this redirects to itself forever
        if (($slug ?? '') !== $question->slug) {
            return redirect()->route('questions.show', [$question, $question->slug], 301);
        }

        $question->load([
            'user',
            'tags',
            'answers' => fn ($query) => $query->oldest()->orderBy('id'),
            'answers.user',
        ]);

        return view('questions.show', compact('question'));
    }

    public function store(StoreQuestionRequest $request)
    {
        $question = DB::transaction(function () use ($request) {
            // user_id comes from the session, never from input
            $question = new Question($request->safe()->only(['title', 'body']));
            $question->user()->associate($request->user());
            $question->save();

            // match tags by slug so "Laravel" and "laravel" are one tag; forceCreate because slug isn't fillable
            $tagIds = collect(TagParser::parse($request->validated('tags')))
                ->map(fn (string $name) => Tag::where('slug', Str::slug($name))->first()
                    ?? Tag::forceCreate(['name' => $name, 'slug' => Str::slug($name)]))
                ->pluck('id');

            $question->tags()->sync($tagIds);

            return $question;
        });

        return redirect()
            ->route('questions.show', [$question, $question->slug])
            ->with('status', 'Your question was posted.');
    }
}