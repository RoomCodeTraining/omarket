<?php

declare(strict_types=1);

namespace App\Actions\Cargos;

use App\Models\Cargo;
use Carbon\CarbonInterface;

final class GenerateCargoCode
{
    public function handle(?CarbonInterface $date = null): string
    {
        $prefix = 'CG-'.($date ?? now())->format('ym');
        $suffix = 1;

        do {
            $code = sprintf('%s-%04d', $prefix, $suffix);
            $suffix++;
        } while (Cargo::query()->where('code', $code)->exists());

        return $code;
    }
}
