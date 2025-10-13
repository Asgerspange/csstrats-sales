<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes
};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BunnyUpload extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'upload_id',
        'original_name',
        'file_path',
        'chunks_path',
        'file_size',
        'mime_type',
        'status',
        'chunks_uploaded',
        'total_chunks',
        'cdn_url',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'chunks_uploaded' => 'integer',
        'total_chunks' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the upload
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the demo associated with this upload
     */
    public function demo(): HasOne
    {
        return $this->hasOne(Demo::class, 'upload_id', 'id');
    }

    /**
     * Get upload progress percentage
     */
    public function getProgressAttribute(): float
    {
        if ($this->total_chunks === 0) {
            return 0;
        }

        return ($this->chunks_uploaded / $this->total_chunks) * 100;
    }

    /**
     * Check if upload is complete
     */
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if upload is in progress
     */
    public function isUploading(): bool
    {
        return $this->status === 'uploading';
    }

    /**
     * Check if upload failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if upload was cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes === 0) {
            return '0 Bytes';
        }

        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }

    /**
     * Get the CDN URL for the uploaded file
     */
    public function getCdnUrlAttribute($value): ?string
    {
        if (!$value && $this->isComplete()) {
            return config('services.bunny.cdn_url') . '/' . $this->file_path;
        }

        return $value;
    }

    /**
     * Scope to get uploads by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get active uploads
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'uploading');
    }

    /**
     * Scope to get completed uploads
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed uploads
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}