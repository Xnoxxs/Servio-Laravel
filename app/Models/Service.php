<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

#[Fillable(['title', 'description', 'price_cents', 'duration_minutes', 'location'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'duration_minutes' => 'integer',
            'is_published' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Service>  $query
     * @return Builder<Service>
     */
    #[Scope]
    protected function forPublicBrowsing(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->with(['category', 'provider'])
            ->latest('id');
    }

    /** @return list<string> */
    public function photoUrls(): array
    {
        if (! $this->image_path) {
            return [];
        }

        $disk = Storage::disk('public');
        $photos = [$disk->url($this->image_path)];

        if (str_starts_with($this->image_path, 'demo-services/')) {
            foreach ([2, 3] as $position) {
                $path = dirname($this->image_path).'/'.$position.'.webp';

                if ($disk->exists($path)) {
                    $photos[] = $disk->url($path);
                }
            }
        }

        return $photos;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, Booking::class);
    }
}
