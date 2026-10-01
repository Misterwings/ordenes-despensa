<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateOrderRequest;
use App\Models\Item;
use App\Models\Order;
use App\Services\ExcelParser;
use App\Services\OrderGenerator;
use App\Services\PdfExporter;
use App\Services\SedeCatalog;
use App\Services\XlsxExporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with('user:id,name')
            ->withCount('orderItems')
            ->latest('fecha')
            ->latest('id')
            ->paginate(15)
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'remision' => $order->remision,
                'sede' => $order->sede,
                'fecha' => $order->fecha->format('d/m/Y'),
                'user_name' => $order->user?->name ?? 'Sin registrar',
                'items_count' => $order->order_items_count,
                'subtotal' => $order->subtotal,
                'iva' => $order->iva,
                'total' => $order->total,
            ]);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'canDeleteOrders' => $request->user()?->id !== 3,
        ]);
    }

    public function create(SedeCatalog $sedes)
    {
        return Inertia::render('Orders/Create', [
            'sedes' => $sedes->options(),
        ]);
    }

    public function preview(GenerateOrderRequest $request, ExcelParser $parser, OrderGenerator $generator, SedeCatalog $sedes)
    {
        $parsed = $parser->parse($request->file('archivo'));
        $sedeResolution = $sedes->resolveForParsedItems($parsed, $request->sede);
        $parsed = $sedeResolution['parsed_items'];

        $this->ensureUniqueRemisionForSede($request->remision, $sedeResolution['sede']);

        $orderData = $generator->generate($parsed);

        return Inertia::render('Orders/Preview', [
            'orderData' => $orderData,
            'remision' => $request->remision,
            'sede' => $sedeResolution['sede'],
            'fecha' => $request->fecha,
            'operationCenter' => $sedeResolution['operation_center'],
            'items' => Item::with('category')->orderBy('codigo_item')->get(),
        ]);
    }

    public function store(GenerateOrderRequest $request, ExcelParser $parser, OrderGenerator $generator, SedeCatalog $sedes)
    {
        $parsed = $parser->parse($request->file('archivo'));
        $sedeResolution = $sedes->resolveForParsedItems($parsed, $request->sede);
        $parsed = $sedeResolution['parsed_items'];

        $this->ensureUniqueRemisionForSede($request->remision, $sedeResolution['sede']);
        $this->ensurePresentationsMatch($parsed, $generator);

        if ($request->filled('manual_items')) {
            foreach ($request->manual_items as $manual) {
                $parsed->push([
                    'codigo_item' => $manual['codigo_item'],
                    'cantidad' => (int) $manual['cantidad'],
                ]);
            }
        }

        try {
            $order = $generator->store($parsed, [
                ...$request->only(['remision', 'sede', 'fecha']),
                'sede' => $sedeResolution['sede'],
                'user_id' => $request->user()->id,
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateRemisionForSedeException($exception)) {
                $this->throwDuplicateRemisionForSedeValidation($request->remision, $sedeResolution['sede']);
            }

            throw $exception;
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pedido creado exitosamente.');
    }

    public function show(Order $order)
    {
        $order->load('user', 'orderItems.item.category');

        return Inertia::render('Orders/Show', ['order' => $order]);
    }

    public function exportXlsx(Order $order, XlsxExporter $exporter)
    {
        return $exporter->export($order);
    }

    public function exportPdf(Order $order, PdfExporter $exporter)
    {
        return $exporter->export($order);
    }

    public function destroy(Request $request, Order $order)
    {
        abort_if($request->user()?->id === 3, 403);

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Pedido eliminado exitosamente.');
    }

    private function ensureUniqueRemisionForSede(string $remision, string $sede): void
    {
        $exists = Order::query()
            ->where('remision', $remision)
            ->where('sede', $sede)
            ->exists();

        if ($exists) {
            $this->throwDuplicateRemisionForSedeValidation($remision, $sede);
        }
    }

    private function ensurePresentationsMatch(Collection $parsed, OrderGenerator $generator): void
    {
        $mismatches = $generator->generate($parsed)['presentation_mismatches'];

        if ($mismatches === []) {
            return;
        }

        $messages = collect($mismatches)->map(function (array $mismatch) {
            $row = $mismatch['fila'] ? "Fila {$mismatch['fila']}, " : '';
            $excel = $mismatch['unidad_medida_excel'] ?? 'vacía';

            return "{$row}producto {$mismatch['codigo_item']}: U.M del Excel '{$excel}' no coincide con Presentación '{$mismatch['presentacion_producto']}'.";
        })->implode(' ');

        throw ValidationException::withMessages(['archivo' => $messages]);
    }

    private function throwDuplicateRemisionForSedeValidation(string $remision, string $sede): never
    {
        throw ValidationException::withMessages([
            'remision' => "Ya existe una orden de compra con la remisión {$remision} para la sede {$sede}. Revisa la orden existente antes de crear una nueva.",
        ]);
    }

    private function isDuplicateRemisionForSedeException(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return (string) $exception->getCode() === '23000'
            && (str_contains($message, 'orders_remision_sede_unique')
                || str_contains($message, 'orders.remision'));
    }
}
