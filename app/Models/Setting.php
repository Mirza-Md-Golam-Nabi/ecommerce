<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store settings. Read through App\Settings\StoreSettings, never directly.
 *
 * @property string $key
 * @property mixed $value
 */
#[Table(key: 'key', keyType: 'string', incrementing: false)]
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
