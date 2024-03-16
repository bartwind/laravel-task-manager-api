<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * App\Models\Task
 *
 * @property int $id
 * @property string $title
 * @property bool $is_done
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereIsDone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereUpdatedAt($value)
 * @property int $creator_id
 * @property-read \App\Models\User $creator
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Task whereCreatorId($value)
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'is_done',
        'project_id',
        'scheduled_at',
        'due_at'
    ];

    protected $casts = [
        'is_done' => 'boolean'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopeScheduledBetween(Builder $query, string $fromDate, string $toDate)
    {
        $query
            ->where('scheduled_at', '>=', $fromDate)
            ->where('scheduled_at', '<=', $toDate);
    }

    public function scopeDueBetween(Builder $query, string $fromDate, string $toDate)
    {
        $query
            ->where('due_at', '>=', $fromDate)
            ->where('due_at', '<=', $toDate);
    }

    public function scopeDue(Builder $query, string $filter)
    {
        if ($filter === 'today') {
            $query->where('due_at', '=', Carbon::today()->toDateString());
        } else if ($filter === 'past') {
            $query->where('due_at', '<', Carbon::today()->toDateString());
        }
    }

    protected static function booted()
    {
        static::addGlobalScope('member', function (Builder $builder){
            $builder->where('creator_id', Auth::id())
                ->orWhereIn('project_id', Auth::user()->memberships->pluck('id'));
        });
    }
}
