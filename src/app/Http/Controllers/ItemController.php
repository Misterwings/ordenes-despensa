<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categoryFilter = $request->input('category');

        $items = Item::with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('codigo_item', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%");
                });
            })
            ->when($categoryFilter, function ($query, $categoryFilter) {
                $query->where('categoria_id', $categoryFilter);
            })
            ->orderBy('codigo_item')
            ->paginate(15)
            ->withQueryString();

        $categories = \App\Models\Category::orderBy('orden')->get();

        return Inertia::render('Items/Index', [
            'items' => $items,
            'categories' => $categories,
            'presentaciones' => Item::PRESENTACIONES,
            'filters' => ['search' => $search, 'category' => $categoryFilter],
        ]);
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('orden')->get();

        return Inertia::render('Items/Form', [
            'categories' => $categories,
            'presentaciones' => Item::PRESENTACIONES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo_item' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'precio_unidad' => 'required|numeric|min:0',
            'presentacion' => ['required', 'string', Rule::in(Item::PRESENTACIONES)],
            'precio_presentacion' => 'nullable|numeric|min:0',
            'categoria_id' => 'required|exists:categories,id',
        ]);

        Item::create($validated);

        return Redirect::route('items.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Item $item)
    {
        $item->load('category');
        $categories = \App\Models\Category::orderBy('orden')->get();

        return Inertia::render('Items/Form', [
            'item' => $item,
            'categories' => $categories,
            'presentaciones' => Item::PRESENTACIONES,
        ]);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'codigo_item' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'precio_unidad' => 'required|numeric|min:0',
            'presentacion' => ['required', 'string', Rule::in(Item::PRESENTACIONES)],
            'precio_presentacion' => 'nullable|numeric|min:0',
            'categoria_id' => 'required|exists:categories,id',
        ]);

        $item->update($validated);

        return Redirect::route('items.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return Redirect::route('items.index')->with('success', 'Producto eliminado exitosamente.');
    }

    public function importForm()
    {
        return Inertia::render('Items/Import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        $header = array_shift($rows);
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $row = array_combine($header, $row);
            $row['presentacion'] = strtoupper(trim((string) ($row['presentacion'] ?? '')));

            $validator = Validator::make($row, [
                'codigo_item' => 'required|string|max:50',
                'descripcion' => 'required|string|max:255',
                'precio_unidad' => 'required|numeric|min:0',
                'presentacion' => ['required', 'string', Rule::in(Item::PRESENTACIONES)],
                'precio_presentacion' => 'nullable|numeric|min:0',
                'categoria_id' => 'required|exists:categories,id',
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $index + 2,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            Item::create($validator->validated());
            $imported++;
        }

        $message = "{$imported} productos importados exitosamente.";
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' filas con errores.';
        }

        return Redirect::route('items.index')->with('success', $message);
    }
}
