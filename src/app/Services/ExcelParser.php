<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelParser
{
    public function parse(UploadedFile $file): Collection
    {
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->getRowIterator();
        $results = collect();
        $headerRow = null;
        $itemColumn = 0;
        $cantidadColumn = 5;
        $operationCenterColumn = null;
        $unitMeasureColumn = null;

        foreach ($rows as $row) {
            $rowIndex = $row->getRowIndex();
            $cells = $sheet->getRowIterator($rowIndex, $rowIndex)->current()->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $values = [];
            $formattedValues = [];
            foreach ($cells as $cell) {
                $values[] = trim((string) $cell->getValue());
                $formattedValues[] = trim((string) $cell->getFormattedValue());
            }

            // Find header row containing the item column. Quantity and C.O. are optional header lookups.
            if ($headerRow === null) {
                $columns = $this->detectColumns($formattedValues);

                if ($columns['item'] !== null) {
                    $headerRow = $formattedValues;
                    $itemColumn = $columns['item'];
                    $cantidadColumn = $columns['cantidad'] ?? $cantidadColumn;
                    $operationCenterColumn = $columns['operation_center'];
                    $unitMeasureColumn = $columns['unit_measure'];
                }

                continue;
            }

            $itemCode = $this->normalizeItemCode(($values[$itemColumn] ?? '') ?: ($formattedValues[$itemColumn] ?? ''));
            if ($itemCode === null) {
                continue;
            }

            $cantidad = $this->normalizeQuantity(($values[$cantidadColumn] ?? '') ?: ($formattedValues[$cantidadColumn] ?? '0'));

            if ($cantidad <= 0) {
                continue;
            }

            $parsed = [
                'codigo_item' => $itemCode,
                'cantidad' => $cantidad,
                'unidad_medida' => $unitMeasureColumn === null
                    ? null
                    : $this->normalizeUnitMeasure(($values[$unitMeasureColumn] ?? '') ?: ($formattedValues[$unitMeasureColumn] ?? '')),
                'fila' => $rowIndex,
            ];

            if ($operationCenterColumn !== null) {
                $operationCenter = $this->normalizeOperationCenter(($formattedValues[$operationCenterColumn] ?? '') ?: ($values[$operationCenterColumn] ?? ''));

                if ($operationCenter !== null) {
                    $parsed['co'] = $operationCenter;
                }
            }

            $results->push($parsed);
        }

        return $results;
    }

    private function detectColumns(array $values): array
    {
        $columns = [
            'item' => null,
            'cantidad' => null,
            'operation_center' => null,
            'unit_measure' => null,
        ];

        foreach ($values as $index => $value) {
            $header = $this->normalizeHeader($value);

            if ($columns['item'] === null && in_array($header, ['ITEM', 'CODIGOITEM', 'CODITEM'], true)) {
                $columns['item'] = $index;
            }

            if ($columns['cantidad'] === null && str_contains($header, 'CANT')) {
                $columns['cantidad'] = $index;
            }

            if ($columns['operation_center'] === null && in_array($header, ['CO', 'CENTRODEOPERACIONES', 'CENTROOPERACION'], true)) {
                $columns['operation_center'] = $index;
            }

            if ($columns['unit_measure'] === null && in_array($header, ['UM', 'UNIDADMEDIDA', 'UNIDMEDIDA'], true)) {
                $columns['unit_measure'] = $index;
            }
        }

        return $columns;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->toString();
    }

    private function normalizeItemCode(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/^\d+([.,]0+)?$/', $value)) {
            if (! preg_match('/^\d{1,3}([.,]\d{3})+$/', $value)) {
                return null;
            }

            return str_replace([',', '.'], '', $value);
        }

        return preg_replace('/[.,]0+$/', '', $value);
    }

    private function normalizeQuantity(string $value): float
    {
        $value = str_replace(',', '.', $value);

        return (float) preg_replace('/[^0-9.]/', '', $value);
    }

    private function normalizeUnitMeasure(string $value): ?string
    {
        $value = Str::of($value)->ascii()->upper()->squish()->toString();

        return $value === '' ? null : $value;
    }

    private function normalizeOperationCenter(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === '') {
            return null;
        }

        return str_pad($digits, 3, '0', STR_PAD_LEFT);
    }
}
