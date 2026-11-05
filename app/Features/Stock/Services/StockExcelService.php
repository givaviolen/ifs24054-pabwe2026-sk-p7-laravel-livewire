<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class StockExcelService
{
    public const HEADERS = ['name', 'sku', 'quantity', 'unit', 'price', 'min_stock', 'status', 'description'];

    public const MAX_ROWS = 1000;

    public function __construct(private readonly StockService $stocks) {}

    /** @param  Collection<int, Stock>  $stocks  @return string isi file .xlsx */
    public function export(Collection $stocks): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stok');

        foreach (self::HEADERS as $i => $header) {
            $sheet->setCellValueExplicit([$i + 1, 1], $header, DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        foreach ($stocks->values() as $i => $stock) {
            $row = $i + 2;
            // Teks ditulis eksplisit sebagai string: mencegah formula injection (=, +, -, @).
            $sheet->setCellValueExplicit("A{$row}", (string) $stock->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string) $stock->sku, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", $stock->quantity, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("D{$row}", (string) $stock->unit, DataType::TYPE_STRING);
            if ($stock->price !== null) {
                $sheet->setCellValueExplicit("E{$row}", (float) $stock->price, DataType::TYPE_NUMERIC);
            }
            $sheet->setCellValueExplicit("F{$row}", $stock->min_stock, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("G{$row}", $stock->status->value, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$row}", (string) $stock->description, DataType::TYPE_STRING);
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'stk');
        (new Xlsx($spreadsheet))->save($tmp);
        $content = (string) file_get_contents($tmp);
        unlink($tmp);

        return $content;
    }

    /**
     * Import stok dari .xlsx. Baris tidak valid dilewati.
     *
     * @return array{imported:int, skipped:int}
     *
     * @throws InvalidArgumentException bila file tidak dapat dibaca / header "name" tidak ada.
     */
    public function import(User $user, string $path): array
    {
        try {
            $reader = new XlsxReader;
            $reader->setReadDataOnly(true);
            $rows = $reader->load($path)->getActiveSheet()->toArray(null, false, false, false);
        } catch (SpreadsheetException) {
            throw new InvalidArgumentException('File Excel tidak valid atau tidak dapat dibaca.');
        }

        $header = array_map(
            fn ($h) => match ($h = strtolower(trim((string) $h))) {
                'qty' => 'quantity',
                default => $h,
            },
            array_shift($rows) ?? []
        );
        $index = array_flip($header);

        if (! isset($index['name'])) {
            throw new InvalidArgumentException('Kolom "name" tidak ditemukan pada baris header.');
        }

        $valid = [];
        $skipped = 0;

        foreach (array_slice($rows, 0, self::MAX_ROWS) as $row) {
            $cell = fn (string $column) => isset($index[$column]) ? $this->clean($row[$index[$column]] ?? null) : null;

            $data = [
                'name' => $cell('name'),
                'sku' => $cell('sku'),
                'description' => $cell('description'),
                'quantity' => $cell('quantity') ?? 0,
                'unit' => $cell('unit') ?? 'pcs',
                'price' => $cell('price'),
                'min_stock' => $cell('min_stock') ?? 5,
            ];

            if (Validator::make($data, StockService::rules())->fails()) {
                $skipped++;

                continue;
            }

            $valid[] = $data;
        }

        return [
            'imported' => $this->stocks->createMany($user, $valid),
            'skipped' => $skipped,
        ];
    }

    private function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
