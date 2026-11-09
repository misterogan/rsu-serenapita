<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosingAdjustmentLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['adjustment_id', 'coa_code', 'debit', 'credit', 'memo'];

    protected $casts = ['debit' => 'float', 'credit' => 'float'];
}
