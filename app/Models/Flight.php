<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'airline', 'flight_code', 'origin', 'destination', 'departure_at',
    'arrival_at', 'duration_minutes', 'stops', 'baggage_description', 'total_price_cop',
])]
class Flight extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'departure_at' => 'immutable_datetime:Y-m-d\TH:i:sP',
            'arrival_at' => 'immutable_datetime:Y-m-d\TH:i:sP',
            'duration_minutes' => 'integer',
            'stops' => 'integer',
            'total_price_cop' => 'integer',
        ];
    }
}
