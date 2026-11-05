<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockExcelService $excel;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->excel = app(StockExcelService::class);
        $this->user = User::factory()->create();
    }

    /** Tulis array 2D menjadi file .xlsx sementara. */
    private function xlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_export_writes_header_and_rows(): void
    {
        $a = Stock::factory()->for($this->user)->create(['name' => 'Kertas', 'sku' => 'K1', 'quantity' => 8, 'unit' => 'rim', 'price' => 52000, 'min_stock' => 5, 'status' => 'available', 'description' => 'Putih']);
        $b = Stock::factory()->for($this->user)->create(['name' => '=1+1', 'sku' => null, 'quantity' => 0, 'price' => null, 'status' => 'out_of_stock', 'description' => null]);

        $binary = $this->excel->export(collect([$a, $b->fresh()]));

        $path = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        file_put_contents($path, $binary);
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
        unlink($path);

        $this->assertSame(StockExcelService::HEADERS, $rows[0]);
        $this->assertEquals(['Kertas', 'K1', 8, 'rim', 52000, 5, 'available', 'Putih'], $rows[1]);
        $this->assertSame('=1+1', $rows[2][0]);   // tetap teks, bukan formula
        $this->assertNull($rows[2][4]);            // price kosong
        $this->assertSame('out_of_stock', $rows[2][6]);
    }

    public function test_export_empty_collection_has_only_header(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        file_put_contents($path, $this->excel->export(collect()));
        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false, false);
        unlink($path);

        $this->assertCount(1, $rows);
    }

    public function test_import_valid_rows_and_skips_invalid(): void
    {
        $path = $this->xlsx([
            ['Name', 'SKU', 'Qty', 'Unit', 'Price', 'Min_Stock', 'Description'],
            ['Kabel', 'KB-1', 3, 'pcs', 15000, 5, 'Kabel **USB**'],
            ['Mouse', '', 40, 'pcs', '', 5, ''],
            ['', 'X', 1, 'pcs', 1, 1, ''],          // tanpa nama -> dilewati
            ['Rusak', 'R', 'banyak', 'pcs', 1, 1, ''], // qty bukan angka -> dilewati
        ]);

        $result = $this->excel->import($this->user, $path);
        unlink($path);

        $this->assertSame(['imported' => 2, 'skipped' => 2], $result);
        $kabel = Stock::where('name', 'Kabel')->first();
        $this->assertSame($this->user->id, $kabel->user_id);
        $this->assertSame('low_stock', $kabel->status->value);
        $this->assertSame('available', Stock::where('name', 'Mouse')->first()->status->value);
    }

    public function test_import_uses_defaults_for_missing_columns(): void
    {
        $path = $this->xlsx([['name'], ['Hanya Nama']]);

        $result = $this->excel->import($this->user, $path);
        unlink($path);

        $this->assertSame(1, $result['imported']);
        $stock = Stock::first();
        $this->assertSame(0, $stock->quantity);
        $this->assertSame('pcs', $stock->unit);
        $this->assertSame(5, $stock->min_stock);
        $this->assertSame('out_of_stock', $stock->status->value);
    }

    public function test_import_requires_name_header(): void
    {
        $path = $this->xlsx([['sku', 'qty'], ['A', 1]]);

        try {
            $this->excel->import($this->user, $path);
            $this->fail('Seharusnya melempar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('name', $e->getMessage());
        } finally {
            unlink($path);
        }
    }

    public function test_import_rejects_non_xlsx_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'bad');
        file_put_contents($path, 'bukan file excel');

        try {
            $this->excel->import($this->user, $path);
            $this->fail('Seharusnya melempar InvalidArgumentException');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('tidak valid', $e->getMessage());
        } finally {
            unlink($path);
        }
    }

    public function test_export_then_import_round_trip(): void
    {
        Stock::factory()->count(3)->for($this->user)->create();
        $other = User::factory()->create();

        $path = tempnam(sys_get_temp_dir(), 'rt').'.xlsx';
        file_put_contents($path, $this->excel->export($this->user->stocks()->get()));

        $result = $this->excel->import($other, $path);
        unlink($path);

        $this->assertSame(3, $result['imported']);
        $this->assertSame(3, $other->stocks()->count());
    }
}
