<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = ['type', 'title', 'album', 'image', 'video_url', 'caption', 'taken_at', 'show', 'sort'];
    protected $casts = ['show' => 'bool', 'taken_at' => 'date'];

    /** ID video YouTube dari berbagai bentuk tautan (watch, youtu.be, shorts, embed). */
    public static function youtubeId(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    public function videoId(): ?string
    {
        return self::youtubeId($this->video_url);
    }

    public function thumbUrl(): ?string
    {
        if ($this->image) {
            return asset('storage/'.$this->image);
        }

        return $this->videoId() ? 'https://i.ytimg.com/vi/'.$this->videoId().'/hqdefault.jpg' : null;
    }
}
