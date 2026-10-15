<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slide extends Model
{
    protected $fillable = ['title', 'text', 'tab', 'button', 'link', 'active', 'sort', 'image', 'image_position', 'image_fit'];
    protected $casts = ['active' => 'bool'];

    /**
     * Cara foto latar ditampilkan: "cover" (penuh, dipotong) atau "contain" (utuh).
     * Mode otomatis memakai "contain" untuk foto tegak atau hampir persegi, supaya tidak terpotong.
     */
    public function fitMode(): string
    {
        if (! $this->image) {
            return 'cover';
        }
        if (in_array($this->image_fit, ['cover', 'contain'], true)) {
            return $this->image_fit;
        }
        $path = Storage::disk('public')->path($this->image);
        $size = is_file($path) ? @getimagesize($path) : false;
        if (! $size || ! $size[1]) {
            return 'cover';
        }

        return ($size[0] / $size[1]) >= 1.6 ? 'cover' : 'contain';
    }
}
