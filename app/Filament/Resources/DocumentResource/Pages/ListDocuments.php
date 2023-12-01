<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListDocuments extends ListRecords
{
    public function getTitle(): string|Htmlable
    {
        return 'All documents';
    }

    protected static ?string $navigationLabel = 'All documents';

    protected static string $resource = DocumentResource::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    protected function getHeaderActions(): array
    {
        return DocumentResource::getHeaderActions();
    }
}
