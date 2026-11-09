<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pemetaan satu rekening Khanza ke satu akun COA baru. */
class ClosingMapping extends Model
{
    protected $fillable = ['kd_rek', 'coa_code', 'user_name'];
}
