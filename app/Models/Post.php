<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'title', 'script_body', 'media_links', 'media_files', 'scheduled_at', 'status', 'posted_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_POSTED = 'posted';

    public const STATUS_SKIPPED = 'skipped';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_links' => 'array',
            'media_files' => 'array',
            'scheduled_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<SocialAccount, $this>
     */
    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(SocialAccount::class, 'post_platform')
            ->withPivot(['options', 'status', 'platform_scheduled_at', 'posted_at'])
            ->withTimestamps();
    }
}
