<?php

namespace App\Models;

use App\Enums\TypeConsultation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Table('consultations')]
#[Fillable(['type', 'consultable_id', 'consulte_le'])]
#[WithoutTimestamps]
class Consultation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeConsultation::class,
            'consulte_le' => 'datetime',
        ];
    }
}
