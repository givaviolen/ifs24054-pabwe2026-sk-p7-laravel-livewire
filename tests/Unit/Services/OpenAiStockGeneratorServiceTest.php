<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'openai.api_key' => 'sk-test',
            'openai.base_url' => 'https://api.openai.test/v1',
            'openai.model' => 'test-model',
            'openai.timeout' => 5,
        ]);
    }

    private function fakeContent(mixed $content, int $status = 200): void
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => $content]]]], $status)]);
    }

    public function test_generates_items_and_sends_expected_request(): void
    {
        $this->fakeContent(json_encode(['items' => [
            ['name' => 'Kertas A4', 'sku' => 'A4', 'description' => 'Kertas', 'quantity' => 10, 'unit' => 'rim', 'price' => 50000, 'min_stock' => 3],
            ['name' => 'Pulpen', 'quantity' => 2],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('perlengkapan kantor', 2);

        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $items);
        $this->assertSame('Kertas A4', $items[0]->name);

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.openai.test/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-test')
                && $request['model'] === 'test-model'
                && $request['response_format'] === ['type' => 'json_object']
                && str_contains($request['messages'][1]['content'], 'perlengkapan kantor')
                && str_contains($request['messages'][1]['content'], 'Buat 2 item');
        });
    }

    public function test_skips_invalid_entries_and_caps_to_requested_count(): void
    {
        $this->fakeContent(json_encode(['items' => [
            'bukan-array',
            ['name' => ''],
            ['name' => 'A'], ['name' => 'B'], ['name' => 'C'],
        ]]));

        $items = (new OpenAiStockGeneratorService)->generate('x', 2);

        $this->assertSame(['A', 'B'], array_map(fn ($i) => $i->name, $items));
    }

    public function test_count_is_clamped(): void
    {
        $this->fakeContent(json_encode(['items' => [['name' => 'A']]]));

        (new OpenAiStockGeneratorService)->generate('x', 999);

        Http::assertSent(fn (Request $r) => str_contains($r['messages'][1]['content'], 'Buat 20 item'));
    }

    public function test_missing_api_key(): void
    {
        config(['openai.api_key' => null]);
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OPENAI_API_KEY');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_http_failure(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 500');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_connection_failure(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak dapat menghubungi');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_invalid_json_content(): void
    {
        $this->fakeContent('bukan json');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak valid');
        (new OpenAiStockGeneratorService)->generate('x');
    }

    public function test_json_without_items_key(): void
    {
        $this->fakeContent(json_encode(['foo' => 'bar']));

        $this->expectException(RuntimeException::class);
        (new OpenAiStockGeneratorService)->generate('x');
    }
}
