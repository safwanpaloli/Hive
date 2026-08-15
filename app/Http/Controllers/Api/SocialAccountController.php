<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialAccountRequest;
use App\Models\SocialAccount;
use App\Services\Analytics\SocialAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocialAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $accounts = $request->user()
            ->socialAccounts()
            ->when($request->filled('platform'), fn ($q) => $q->where('platform_name', $request->string('platform')))
            ->orderBy('platform_name')
            ->orderBy('handle')
            ->get();

        return response()->json(['accounts' => $accounts]);
    }

    public function store(SocialAccountRequest $request, SocialAnalyticsService $analytics): JsonResponse
    {
        $account = $request->user()->socialAccounts()->create($this->prepareData($request));
        $analytics->forget($request->user());

        return response()->json(['account' => $account], 201);
    }

    public function show(Request $request, SocialAccount $socialAccount): JsonResponse
    {
        abort_if($socialAccount->user_id !== $request->user()->id, 403, 'You do not own this account.');

        return response()->json(['account' => $socialAccount]);
    }

    public function update(SocialAccountRequest $request, SocialAccount $socialAccount, SocialAnalyticsService $analytics): JsonResponse
    {
        abort_if($socialAccount->user_id !== $request->user()->id, 403, 'You do not own this account.');

        $data = $this->prepareData($request);

        if ($request->hasFile('avatar')) {
            $this->deleteAvatar($socialAccount);
        }

        $socialAccount->update($data);
        $analytics->forget($request->user());

        return response()->json(['account' => $socialAccount->fresh()]);
    }

    public function destroy(Request $request, SocialAccount $socialAccount, SocialAnalyticsService $analytics): JsonResponse
    {
        abort_if($socialAccount->user_id !== $request->user()->id, 403, 'You do not own this account.');

        $this->deleteAvatar($socialAccount);
        $socialAccount->delete();
        $analytics->forget($request->user());

        return response()->json(['message' => 'Account deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareData(SocialAccountRequest $request): array
    {
        $data = $request->validated();
        unset($data['avatar']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar_url'] = Storage::disk('public')->url($path);
        } elseif (array_key_exists('avatar_url', $data) && empty($data['avatar_url'])) {
            $data['avatar_url'] = null;
        }

        return $data;
    }

    private function deleteAvatar(SocialAccount $socialAccount): void
    {
        $relative = $this->relativeStoragePath((string) $socialAccount->avatar_url);

        if ($relative !== null) {
            Storage::disk('public')->delete($relative);
        }
    }

    private function relativeStoragePath(string $url): ?string
    {
        $prefix = Storage::disk('public')->url('');

        if (! str_starts_with($url, $prefix)) {
            return null;
        }

        return substr($url, strlen($prefix));
    }
}
