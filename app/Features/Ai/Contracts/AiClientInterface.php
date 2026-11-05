<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedStockItem;
use RuntimeException;

interface AiClientInterface
{
    /**
     * Hasilkan item inventaris dari deskripsi bebas.
     *
     * @return array<int, GeneratedStockItem>
     *
     * @throws RuntimeException bila layanan AI tidak tersedia / respons tidak valid.
     */
    public function generate(string $prompt, int $count = 5): array;
}
