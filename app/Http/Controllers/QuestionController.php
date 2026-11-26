<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Tag;
use App\Support\TagParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Vote;

class QuestionController extends Controller
{

    public function create()
    {
        return view('questions.create');
    }
   public function show(Request $request, Question $question, ?string $slug = null)
    {
        if (($slug ?? '') !== $question->slug) {
            return redirect()->route('questions.show', [$question, $question->slug], 301);
        }

        $question->loadSum('votes as score', 'value');
        $question->load([
            'user',
            'tags',
            'answers' => fn ($query) => $query->withSum('votes as score', 'value')->oldest()->orderBy('id'),
            'answers.user',
        ]);

        // the viewer's own votes on this page: one query, whatever the number of answers
        $myVotes = $request->user()
            ? Vote::where('user_id', $request->user()->id)
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('votable_type', 'question')->where('votable_id', $question->id))
                    ->orWhere(fn ($q) => $q->where('votable_type', 'answer')->whereIn('votable_id', $question->answers->modelKeys())))
                ->get()
                ->mapWithKeys(fn (Vote $vote) => ["{$vote->votable_type}:{$vote->votable_id}" => $vote->value])
            : collect();

        return view('questions.show', compact('question', 'myVotes'));
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