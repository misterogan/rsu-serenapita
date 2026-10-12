<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phone extends Model
{
    protected $fillable = ['label', 'number', 'kind', 'show_top', 'show_igd', 'show_contact', 'show_footer', 'sort'];
    protected $casts = ['show_top' => 'bool', 'show_igd' => 'bool', 'show_contact' => 'bool', 'show_footer' => 'bool'];

    public function href(): string
    {
        $digits = preg_replace('/\D/', '', $this->number);
        if ($this->kind === 'wa') {
            $digits = str_starts_with($digits, '0') ? '62'.substr($digits, 1) : $digits;
            return 'https://wa.me/'.$digits;
        }

        return 'tel:'.$digits;
    }
}
