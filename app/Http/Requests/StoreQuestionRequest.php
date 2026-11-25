<?php

namespace App\Http\Requests;

use App\Support\TagParser;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // the route's auth middleware decides who gets here
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'body'  => ['required', 'string', 'min:20', 'max:10000'],
            'tags'  => ['required', 'string', 'max:200', function (string $attribute, mixed $value, Closure $fail) {
                $tags = TagParser::parse($value);

                if (count($tags) < 1 || count($tags) > 5) {
                    $fail('Add between 1 and 5 tags, separated by commas.');

                    return;
                }

                foreach ($tags as $tag) {
                    if (mb_strlen($tag) > 30 || Str::slug($tag) === '') {
                        $fail("\"$tag\" is not a valid tag (up to 30 characters, with letters or numbers).");

                        return;
                    }
                }
            }],
        ];
    }
}