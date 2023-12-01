<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SharedWithMePage extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Shared with me';
    }

    protected static ?string $navigationLabel = 'Shared with me';

    protected function getHeaderActions(): array
    {
        return DocumentResource::getHeaderActions();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::whereIn('id', Auth::user()->sharedDocuments->pluck('id')->toArray()))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Owner name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('File name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')->label('File size ( kb )'),
                Tables\Columns\TextColumn::make('extension')->label('File type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('Type')
                    ->options(Document::select('extension')
                        ->get()
                        ->map(function ($row) {
                            $extension = $row->extension;

                            return [$extension => ucwords($extension)];
                        })
                        ->collapse()
                        ->toArray())
                    ->attribute('extension'),
            ])
            ->actions([
                //                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('Open')->icon('heroicon-s-eye')->openUrlInNewTab()->url(fn (Document $record): string => Storage::url($record->url)),
                Tables\Actions\Action::make('Download')->icon('heroicon-m-cloud-arrow-down')->color('success')->url(fn (Document $record): string => route('documents.download', ['document' => $record->id])),
                Tables\Actions\Action::make('Stop sharing')->color('danger')->action(function (Document $record) {Auth::user()->sharedDocs()->detach($record->id); return redirect()->route('filament.admin.resources.documents.shared_with_me')
                    ;}),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('download')->icon('heroicon-o-document-arrow-down')->action(function (Collection $records) {
                        // loop through all records and add them to a zip file and download it
                        $path = storage_path('app/public/'.Auth::id().'/zips');
                        if(!File::isDirectory($path)){
                            File::makeDirectory($path, 0777, true, true);
                        }
                        $zip = new \ZipArchive();
                        $zip_file = storage_path('app/public/'.Auth::id().'/zips/'.count($records).'_my_doc_records.zip');
                        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                        $config  = json_decode(file_get_contents(base_path('public/'.config('filesystems.disks.gcs.key_file_path'))), true);
                        $storage = new StorageClient($config);
                        $bucket  = $storage->bucket(config('filesystems.disks.gcs.bucket'));
                        //                        dd(storage_path('app/public/'.Auth::id().'/zips/hop.zip'),Storage::url(Auth::id().'/zips/hop.zip'));
                        foreach($records as $record){
                            if (Storage::exists($record->url)) {
                                $object  = $bucket->object($record->url);
                                $tempFile = $record->name;
                                $result = Storage::disk('public')->put($tempFile, $object->downloadAsStream());
                                if($result) {
                                    $zip->addFile(storage_path('app/public/'.$tempFile), $record->name);
                                }
                            }
                        };
                        $zip->close();
                        foreach($records as $record){
                            $tempFile = 'file-'.$record->id.'.' . $record->extension;
                            Storage::disk('public')->delete($tempFile);
                        }
                        return response()->download($zip_file);
                    }
                    ),

                    BulkAction::make('Stop sharing')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                Auth::user()->sharedDocs()->detach($record->id);
                            }
                            return redirect()->route('filament.admin.resources.documents.shared_with_me');
                        }),
                ]),
            ]);
    }
}
