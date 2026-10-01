<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pedido {{ $order->remision }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; }
        th { background: #eee; font-weight: bold; }
        .header { margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16pt; }
        .header p { margin: 2px 0; }
        .category-title { font-weight: bold; font-size: 11pt; margin-top: 15px; }
        .totals { margin-top: 20px; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo_despensa.png');
        $logoBase64 = base64_encode(file_get_contents($logoPath));
    @endphp

    <div class="header">
        <table style="border: none; margin-bottom: 5px;">
            <tr>
                <td style="border: none; width: 120px;">
                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo" style="max-width: 100px; max-height: 80px;">
                </td>
                <td style="border: none;">
                    <h1 style="margin: 0; font-size: 16pt;">MES GROUP S.A.S - LA DESPENSA 2012</h1>
                    <p style="margin: 2px 0;">Remisión: {{ $order->remision }}</p>
                    <p style="margin: 2px 0;">Sede: {{ $order->sede }}</p>
                    <p style="margin: 2px 0;">Fecha: {{ $order->fecha->format('d/m/Y') }}</p>
                    <p style="margin: 2px 0;">Realizado por: {{ $order->user?->name ?? 'Sin registrar' }}</p>
                    <p style="margin: 2px 0;"><em>NOTA: LOS PRECIOS NO INCLUYEN IVA</em></p>
                </td>
            </tr>
        </table>
    </div>

    @foreach($grouped as $catId => $orderItems)
        @php $category = $orderItems->first()->item->category; @endphp
        <div class="category-title">{{ $category->nombre }}</div>
        <table>
            <thead>
                <tr>
                    <th>CÓDIGO</th>
                    <th>PRODUCTO</th>
                    <th class="right">PRECIO UND</th>
                    <th>PRESENTACIÓN</th>
                    <th class="right">PRECIO PRES</th>
                    <th class="center">CANTIDAD</th>
                    <th class="right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderItems as $oi)
                <tr>
                    <td>{{ $oi->item->codigo_item }}</td>
                    <td>{{ $oi->item->descripcion }}</td>
                    <td class="right">{{ number_format($oi->precio_unitario, 0, ',', '.') }}</td>
                    <td>{{ $oi->presentacion ?? $oi->item->presentacion }}</td>
                    <td class="right">{{ number_format($oi->precio_presentacion, 0, ',', '.') }}</td>
                    <td class="center">{{ $oi->cantidad }}</td>
                    <td class="right">{{ number_format($oi->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="6" class="right"><strong>TOTAL {{ $category->nombre }}</strong></td>
                    <td class="right"><strong>{{ number_format($orderItems->sum('total'), 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <div class="totals">
        <table>
            <tr><td class="right">SUBTOTAL</td><td class="right">{{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
            <tr><td class="right">IVA</td><td class="right">{{ number_format($order->iva, 0, ',', '.') }}</td></tr>
            <tr><td class="right"><strong>TOTAL</strong></td><td class="right"><strong>{{ number_format($order->total, 0, ',', '.') }}</strong></td></tr>
        </table>
    </div>
</body>
</html>
