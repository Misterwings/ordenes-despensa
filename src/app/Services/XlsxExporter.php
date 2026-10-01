<?php

namespace App\Services;

use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class XlsxExporter
{
    public function export(Order $order): BinaryFileResponse
    {
        $order->load('user', 'orderItems.item.category');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PEDIDO');

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);

        $row = 1;

        // Company header
        $sheet->setCellValue('C' . $row, 'MES GROUP S.A.S');
        $sheet->setCellValue('C' . ($row + 1), 'LA DESPENSA 2012');
        $sheet->setCellValue('E' . $row, 'FECHA');
        $sheet->setCellValue('F' . $row, $order->fecha->format('d/m/Y'));
        $row += 2;
        $sheet->setCellValue('E' . $row, 'SEDE');
        $sheet->setCellValue('F' . $row, $order->sede);
        $row++;
        $sheet->setCellValue('E' . $row, 'REMISION #');
        $sheet->setCellValue('F' . $row, $order->remision);
        $row++;
        $sheet->setCellValue('E' . $row, 'REALIZADO POR');
        $sheet->setCellValue('F' . $row, $order->user?->name ?? 'Sin registrar');
        $row++;
        $sheet->setCellValue('A' . $row, 'NOTA: LOS PRECIOS NO INCLUYEN IVA');
        $row++;

        // Header row
        $row++;
        $headers = ['ITEM', 'PRODUCTO', 'PRECIO UND', 'PRESENTACION', 'PRECIO UND', 'CANT. PEDIDO', 'TOTAL'];
        foreach ($headers as $i => $header) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->getFont()->setBold(true);
        }
        $row++;

        $currentCategory = null;
        $globalRowStart = $row;

        foreach ($order->orderItems->groupBy(fn ($oi) => $oi->item->category->id) as $catId => $orderItems) {
            $category = $orderItems->first()->item->category;

            // Category header
            $sheet->setCellValue('B' . $row, $category->nombre);
            $sheet->getStyle('B' . $row)->getFont()->setBold(true);
            $row++;

            foreach ($orderItems as $oi) {
                $sheet->setCellValue('A' . $row, $oi->item->codigo_item);
                $sheet->setCellValue('B' . $row, $oi->item->descripcion);
                $sheet->setCellValue('C' . $row, $oi->precio_unitario);
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('D' . $row, $oi->presentacion ?? $oi->item->presentacion);
                $sheet->setCellValue('E' . $row, $oi->precio_presentacion);
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('F' . $row, $oi->cantidad);
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->setCellValue('G' . $row, $oi->total);
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $row++;
            }

            // Subtotal for category
            $catSubtotal = $orderItems->sum('total');
            $sheet->setCellValue('F' . $row, 'TOTAL');
            $sheet->setCellValue('G' . $row, $catSubtotal);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('G' . $row)->getFont()->setBold(true);
            $row++;
        }

        // Totals
        $row++;
        $sheet->setCellValue('E' . $row, 'SUBTOTAL');
        $sheet->setCellValue('G' . $row, $order->subtotal);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('E' . $row, 'IVA');
        $sheet->setCellValue('G' . $row, $order->iva);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('E' . $row, 'TOTAL');
        $sheet->setCellValue('G' . $row, $order->total);
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('G' . $row)->getFont()->setBold(true);

        // Borders
        $styleArray = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
        $sheet->getStyle('A' . $globalRowStart . ':G' . $row)->applyFromArray($styleArray);

        $tempFile = tempnam(sys_get_temp_dir(), 'pedido_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        $response = new BinaryFileResponse($tempFile);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'PEDIDO_' . $order->remision . '_' . $order->fecha->format('Ymd') . '.xlsx'
        );

        return $response;
    }
}
