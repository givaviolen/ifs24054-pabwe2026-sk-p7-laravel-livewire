<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    public function generate(string $prompt, int $count = 5): array
    {
        $apiKey = config('openai.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }

        $count = max(1, min(20, $count));

        try {
            $response = Http::withToken($apiKey)
                ->baseUrl((string) config('openai.base_url'))
                ->timeout((int) config('openai.timeout'))
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => config('openai.model'),
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => "Buat {$count} item inventaris stok barang untuk kebutuhan berikut:\n".$prompt],
                    ],
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Tidak dapat menghubungi layanan AI.');
        }

        if ($response->failed()) {
            throw new RuntimeException('Layanan AI gagal merespons (HTTP '.$response->status().').');
        }

        $decoded = json_decode((string) data_get($response->json(), 'choices.0.message.content', ''), true);
        $items = is_array($decoded) ? ($decoded['items'] ?? null) : null;

        if (! is_array($items)) {
            throw new RuntimeException('Respons AI tidak valid.');
        }

        $result = [];

        foreach ($items as $item) {
            $dto = is_array($item) ? GeneratedStockItem::fromArray($item) : null;

            if ($dto !== null) {
                $result[] = $dto;
            }
        }

        return array_slice($result, 0, $count);
    }

    private function systemPrompt(): string
    {
        return <<<'TXT'
Kamu adalah asisten manajemen inventaris. Hasilkan daftar item stok barang yang realistis.
Balas HANYA dengan JSON valid berbentuk:
{"items":[{"name":string,"sku":string,"description":string (Markdown singkat),"quantity":integer,"unit":string,"price":number|null,"min_stock":integer}]}
Gunakan bahasa Indonesia. Jangan menambahkan teks di luar JSON.
TXT;
    }
}
