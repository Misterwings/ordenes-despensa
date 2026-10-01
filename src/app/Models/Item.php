<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    public const PRESENTACIONES = ['BOL', 'CAJA', 'CAN', 'TARR', 'UND', 'KG'];

    protected $fillable = [
        'codigo_item', 'descripcion', 'precio_unidad',
        'presentacion', 'precio_presentacion', 'categoria_id',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'categoria_id');
    }
}
