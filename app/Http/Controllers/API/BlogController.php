<?php

namespace App\Http\Controllers\API;

use App\Models\Blog;
use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Blog::with(['author', 'tags'])->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
                'author' => [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                ],
                'tags' => $post->tags->pluck('name')->toArray(),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });

        return response()->json([
            'posts' => $posts
        ]);
    }

    public function show($id)
    {
        $blog = Blog::with(['author', 'tags'])->where('id', $id)->first();

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        $response = [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'content' => $blog->content,
            'author' => [
                'id' => $blog->author->id,
                'name' => $blog->author->name,
            ],
            'tags' => $blog->tags->pluck('name')->toArray(),
            'created_at' => $blog->created_at,
            'updated_at' => $blog->updated_at,
        ];

        return response()->json($response);
    }

    public function showBySlug($slug)
    {
        $post = Blog::with(['author', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'author' => [
                'id' => $post->author->id,
                'name' => $post->author->name,
            ],
            'tags' => $post->tags->pluck('name')->toArray(),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];

        return response()->json($data);
    }



    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'author_id' => 'required|exists:users,id',
            'tags' => 'array',
            'tags.*' => 'string'
        ]);


        $blog = Blog::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'content' => $request->content,
            'author_id' => $request->author_id,
        ]);

        // Handle tags (create if not exists)
        $tagIds = collect($request->input('tags', []))->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName])->id;
        });

        $blog->tags()->sync($tagIds);

        $response = [];
        $response['id'] = $blog->id;
        $response['title'] = $blog->title;
        $response['author'] = $blog->author->name;

        return response()->json([
            "message" => "Post created.",
            "data" => $response,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        $validated = $request->validate([
            'title'     => 'sometimes|required|string|max:255',
            'content'   => 'sometimes|required|string',
            'author_id' => 'sometimes|required|exists:users,id',
            'tags'      => 'sometimes|array',
            'tags.*'    => 'string'
        ]);

        // If title exists in request, regenerate slug
        if ($request->has('title')) {
            $validated['slug'] = Str::slug($request->title);
        }

        $blog->update($validated);

        // Handle tags
        if ($request->has('tags')) {
            $tagIds = collect($request->input('tags'))->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => $tagName])->id;
            });

            $blog->tags()->sync($tagIds);
        }

        $response = [
            'title'  => $blog->title,
            'author' => $blog->author->name,
        ];

        return response()->json([
            "message" => "Post updated.",
            "data"    => $response,
        ], 200);
    }

    public function delete($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }
        $blog->delete();

        return response()->json([
            "message" => "Post deleted.",
        ], 200);
    }

    public function showDeleted()
    {
        $posts = Blog::onlyTrashed()
            ->with(['author', 'tags'])
            // ->whereNotNull('deleted_at') 
            ->get()
            ->map(function ($post) {
                return [
                    'id'         => $post->id,
                    'title'      => $post->title,
                    'slug'       => $post->slug,
                    'content'    => $post->content,
                    'author'     => [
                        'id'   => $post->author->id ?? null,
                        'name' => $post->author->name ?? null,
                    ],
                    'tags'       => $post->tags->pluck('name')->toArray(),
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'deleted_at' => $post->deleted_at,
                ];
            });

        return response()->json([
            'posts' => $posts
        ]);
    }

    public function restore($id)
    {
        $blog = Blog::withTrashed()->find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        // Restore → set deleted_at = NULL
        $blog->restore();

        return response()->json([
            'message' => 'Blog restored successfully.',
            'data'    => [
                'id'      => $blog->id,
                'title'   => $blog->title,
                'deleted_at' => $blog->deleted_at, // sekarang harus NULL
            ]
        ], 200);
    }


    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['message' => 'Blog not found'], 404);
        }

        $blog->tags()->detach();
        $blog->delete();

        return response()->json(['message' => 'Blog deleted']);
    }
}
