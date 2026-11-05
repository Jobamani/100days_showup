<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'slug'       => $this->slug,
            'excerpt'    => $this->excerpt,
            'body'       => $this->when(! $request->routeIs('api.posts.index'), $this->body),
            'image_url'  => $this->image ? Storage::disk('public')->url($this->image) : null,
            'featured'   => $this->featured,
            'author'     => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}