<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Akun COA baru (template COA Serenavita) untuk closing data Khanza. */
class ClosingAccount extends Model
{
    public const TYPES = ['header' => 'Parents/Header', 'sub' => 'Sub Parents/Sub Header', 'detail' => 'Child/Detail'];

    /** Kategori yang masuk neraca; sisanya masuk laba rugi. */
    public const BALANCE_SHEET = ['Aset', 'Kewajiban', 'Ekuitas'];

    /** Kategori bersaldo normal kredit. */
    public const CREDIT_NORMAL = ['Kewajiban', 'Ekuitas', 'Pendapatan'];

    protected $fillable = ['code', 'name', 'parent_code', 'type', 'category', 'company', 'sort'];

    public function isDetail(): bool
    {
        return $this->type === 'detail';
    }

    public static function isBalanceSheet(?string $category): bool
    {
        return in_array($category, self::BALANCE_SHEET, true);
    }

    public static function isCreditNormal(?string $category): bool
    {
        return in_array($category, self::CREDIT_NORMAL, true);
    }
}
