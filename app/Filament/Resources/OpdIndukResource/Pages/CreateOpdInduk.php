<?php

namespace App\Filament\Resources\OpdIndukResource\Pages;

use App\Filament\Concerns\RedirectsToIndex;
use App\Filament\Resources\OpdIndukResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOpdInduk extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = OpdIndukResource::class;
}
