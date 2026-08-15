<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Http\Requests\UpdatePostStatusRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = $request->user()
            ->posts()
            ->with('platforms:id,platform_name,handle,profile_url')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('scheduled_at', '>=', Carbon::parse($request->string('from'))->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('scheduled_at', '<=', Carbon::parse($request->string('to'))->endOfDay()))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', '%'.$request->string('q').'%')->orWhere('script_body', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('platform'), function ($q) use ($request) {
                $q->whereHas('platforms', fn ($p) => $p->where('social_accounts.platform_name', $request->string('platform')));
            })
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_at', Carbon::parse($request->string('date'))->toDateString()))
            ->orderByDesc('scheduled_at')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return response()->json($posts);
    }

    public function store(PostRequest $request): JsonResponse
    {
        $post = $request->user()->posts()->create($request->safe()->except('platform_ids'));

        if ($request->filled('platform_ids')) {
            $post->platforms()->syncWithPivotValues(
                $request->input('platform_ids'),
                ['status' => 'pending']
            );
        }

        return response()->json(['post' => $post->load('platforms')], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'You do not own this post.');

        return response()->json(['post' => $post->load('platforms')]);
    }

    public function update(PostRequest $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'You do not own this post.');

        $post->update($request->safe()->except('platform_ids'));

        if ($request->has('platform_ids')) {
            $post->platforms()->sync($request->input('platform_ids'));
        }

        return response()->json(['post' => $post->load('platforms')]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'You do not own this post.');

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    public function updateStatus(UpdatePostStatusRequest $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'You do not own this post.');

        $status = $request->string('status')->toString();

        $post->update([
            'status' => $status,
            'posted_at' => $status === Post::STATUS_POSTED ? now() : null,
        ]);

        return response()->json(['post' => $post->load('platforms')]);
    }

    public function today(Request $request): JsonResponse
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $posts = $request->user()
            ->posts()
            ->with('platforms:id,platform_name,handle,profile_url')
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereIn('status', [Post::STATUS_SCHEDULED, Post::STATUS_POSTED, Post::STATUS_SKIPPED])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'date' => now()->toDateString(),
            'total' => $posts->count(),
            'posted' => $posts->where('status', Post::STATUS_POSTED)->count(),
            'pending' => $posts->where('status', Post::STATUS_SCHEDULED)->count(),
            'posts' => $posts,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $stats = $request->user()
            ->posts()
            ->where('status', Post::STATUS_POSTED)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('scheduled_at', '>=', Carbon::parse($request->string('from'))->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('scheduled_at', '<=', Carbon::parse($request->string('to'))->endOfDay()))
            ->get()
            ->groupBy(fn (Post $post) => $post->scheduled_at?->toDateString())
            ->map(fn ($group) => [
                'count' => $group->count(),
                'platforms' => $group
                    ->flatMap(fn (Post $post) => $post->platforms->pluck('platform_name'))
                    ->unique()
                    ->values(),
            ]);

        return response()->json([
            'stats' => $stats,
            'total' => $stats->sum('count'),
        ]);
    }
}
