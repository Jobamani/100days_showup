<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Answer;
use App\Models\Comment;
use App\Models\Question;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function question(StoreCommentRequest $request, Question $question)
    {
        return $this->add($request, $question);
    }

    public function answer(StoreCommentRequest $request, Answer $answer)
    {
        return $this->add($request, $answer);
    }

    public function destroy(Request $request, Comment $comment)
    {
        // post comments keep their own counter logic; this route is for Q&A only
        abort_unless(in_array($comment->commentable_type, ['question', 'answer'], true), 404);
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->delete();

        return back()->with('status', 'Comment deleted.');
    }

    private function add(StoreCommentRequest $request, Model $commentable)
    {
        $commentable->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('comment'),
        ]);

        return back()->with('status', 'Comment added.');
    }
}