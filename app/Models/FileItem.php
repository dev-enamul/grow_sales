<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class FileItem extends Model
{
    use HasApiTokens, ActionTrackable;
    protected $table = 'files';

    protected $fillable = [
        'uuid','company_id', 'original_name', 'mime_type', 'disk', 'path', 'size', 'folder_id', 'user_id'
    ];

    protected $appends = [
        'preview_path',
    ];

    protected static function booted()
    {
        static::creating(function (FileItem $file) {
            if (empty($file->uuid)) {
                $file->uuid = (string) Str::uuid();
            }
        });
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fullPath()
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function url()
    {
        return route('file.show', $this->id);
    }

    public function getPreviewPathAttribute()
    {
        if (! $this->path) {
            return null;
        }

        $diskName = $this->disk ?? config('filesystems.default');

        try {
            $disk = Storage::disk($diskName);

            if (method_exists($disk, 'url')) {
                $url = $disk->url($this->path);
                if ($url) {
                    return $this->makeAbsoluteUrl($url);
                }
            }
        } catch (\Throwable $exception) {
            // fall back below
        }

        return route('files.download', $this->id, absolute: true);
    }

    protected function makeAbsoluteUrl(string $url): string
    {
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        return URL::to($url);
    }
}
