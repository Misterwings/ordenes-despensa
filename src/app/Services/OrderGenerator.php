<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderGenerator
{
    public function generate(Collection $parsedItems): array
    {
        $codes = $parsedItems->pluck('codigo_item')->unique();
        $items = Item::with('category')->whereIn('codigo_item', $codes)->get()->keyBy('codigo_item');

        $notFound = $codes->diff($items->keys());
        $grouped = [];
        $presentationMismatches = [];
        $presentationPending = [];

        foreach ($parsedItems as $parsed) {
            $item = $items->get($parsed['codigo_item']);
            if (! $item) {
                continue;
            }

            $catalogPresentation = $this->normalizePresentation($item->presentacion);

            if (in_array($catalogPresentation, Item::PRESENTACIONES, true)) {
                $excelPresentation = $this->normalizePresentation($parsed['unidad_medida'] ?? null);

                if ($excelPresentation !== $catalogPresentation) {
                    $presentationMismatches[] = [
                        'fila' => $parsed['fila'] ?? null,
                        'codigo_item' => $item->codigo_item,
                        'descripcion' => $item->descripcion,
                        'unidad_medida_excel' => $parsed['unidad_medida'] ?? null,
                        'presentacion_producto' => $catalogPresentation,
                    ];
                }
            } else {
                $presentationPending[] = [
                    'fila' => $parsed['fila'] ?? null,
                    'item_id' => $item->id,
                    'codigo_item' => $item->codigo_item,
                    'descripcion' => $item->descripcion,
                ];
            }

            $total = $parsed['cantidad'] * $item->precio_presentacion;
            $catId = $item->category->id;
            $catName = $item->category->nombre;

            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [
                    'category_id' => $catId,
                    'category_name' => $catName,
                    'items' => [],
                    'subtotal' => 0,
                ];
            }

            $grouped[$catId]['items'][] = [
                'codigo_item' => $item->codigo_item,
                'descripcion' => $item->descripcion,
                'precio_unidad' => (float) $item->precio_unidad,
                'presentacion' => $item->presentacion,
                'precio_presentacion' => (float) $item->precio_presentacion,
                'cantidad' => $parsed['cantidad'],
                'total' => $total,
            ];
            $grouped[$catId]['subtotal'] += $total;
        }

        // Sort by category order (fetch from DB)
        $categories = Category::orderBy('orden')->pluck('id')->toArray();
        $sorted = [];
        foreach ($categories as $catId) {
            if (isset($grouped[$catId])) {
                $sorted[] = $grouped[$catId];
            }
        }

        $subtotalGeneral = collect($sorted)->sum('subtotal');
        $ivaCategories = Category::where('aplica_iva', true)->pluck('id');
        $baseIva = 0;
        foreach ($grouped as $catId => $group) {
            if ($ivaCategories->contains($catId)) {
                $baseIva += $group['subtotal'];
            }
        }
        $iva = $baseIva * 0.19;
        $totalGeneral = $subtotalGeneral + $iva;

        return [
            'grouped' => $sorted,
            'subtotal' => $subtotalGeneral,
            'iva' => $iva,
            'total' => $totalGeneral,
            'not_found' => $notFound->values(),
            'presentation_mismatches' => $presentationMismatches,
            'presentation_pending' => $presentationPending,
        ];
    }

    private function normalizePresentation(?string $presentation): ?string
    {
        if ($presentation === null || trim($presentation) === '') {
            return null;
        }

        return Str::of($presentation)->ascii()->upper()->squish()->toString();
    }

    public function store(Collection $parsedItems, array $meta): Order
    {
        $data = $this->generate($parsedItems);

        return DB::transaction(function () use ($data, $meta) {
            $order = Order::create([
                'user_id' => $meta['user_id'] ?? null,
                'remision' => $meta['remision'],
                'sede' => $meta['sede'],
                'fecha' => $meta['fecha'],
                'subtotal' => $data['subtotal'],
                'iva' => $data['iva'],
                'total' => $data['total'],
            ]);

            $items = Item::whereIn('codigo_item', collect($data['grouped'])->pluck('items')->flatten(1)->pluck('codigo_item'))->get()->keyBy('codigo_item');

            foreach ($data['grouped'] as $group) {
                foreach ($group['items'] as $itemData) {
                    $item = $items->get($itemData['codigo_item']);
                    if (! $item) {
                        continue;
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'cantidad' => $itemData['cantidad'],
                        'presentacion' => $itemData['presentacion'],
                        'precio_unitario' => $itemData['precio_unidad'],
                        'precio_presentacion' => $itemData['precio_presentacion'],
                        'total' => $itemData['total'],
                    ]);
                }
            }

            return $order;
        });
    }
}
