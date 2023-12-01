<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Google\Cloud\Storage\StorageClient;
use http\Client\Response;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MyDocumentsPage extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'My documents';
    }

    protected static ?string $navigationLabel = 'My documents';

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    protected function getHeaderActions(): array
    {
        return DocumentResource::getHeaderActions();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::whereIn('id', Auth::user()->documents()->pluck('documents.id')->toArray()))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('File name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')->label('File size')
                    ->searchable(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('Show')->icon('heroicon-s-eye')->url(fn (Document $record): string => Storage::url($record->url))->extraAttributes(['target' => '_blank'
                ]),
                Tables\Actions\Action::make('Download')->icon('heroicon-m-cloud-arrow-down')->color('success')->url(fn (Document $record): string => route('documents.download', ['document' => $record->id])),
                Tables\Actions\Action::make('Share')->icon('heroicon-s-share')->form([
                    Select::make('user_ids')
                        ->label('Share with')
                        ->multiple()
                        ->options(User::all()->pluck('name', 'id')->toArray())
                        ->searchable(['name', 'email']),
                    TextInput::make('email')
                        ->label('Send invitation to email')
                        ->email(),
                    DateTimePicker::make('expires_at')
                        ->label('Expire date?')
                        ->nullable(),
                ])->action(function (array $data, Document $record): void {
                        if (count($data['user_ids']) > 0) {
                            foreach ($data['user_ids'] as $user_id) {
                                $record->sharedTo()->syncWithoutDetaching([$user_id => ['expires_at' => $data['expires_at'], 'created_at' => now()]]);
                            }
                        }
                        if (! empty($data['email'])) {
                            DB::table('documents_users')->insert(['document_id' => $record->id, 'email' => $data['email'], 'expires_at' => $data['expires_at'], 'created_at' => now()]);
                        }
                    }),
                Tables\Actions\Action::make('Stop sharing')->color('danger')->action(function (Document $record) {DB::table('document_users')->where('document_id'.$record->id)->delete(); return redirect()->route('filament.admin.resources.documents.my_documents');})
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    BulkAction::make('Download')->icon('heroicon-o-document-arrow-down')->action(function (Collection $records) {
                        // loop through all records and add them to a zip file and download it
                        $path = storage_path('app/public/'.Auth::id().'/zips');
                        if(!File::isDirectory($path)){
                            File::makeDirectory($path, 0777, true, true);
                        }
                        $zip = new \ZipArchive();
                        $zip_file = $path.'/my_doc_records_'.now()->format('Y-m-d-H-i-s').'.zip';
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
                    }),
                    BulkAction::make('Share')->icon('heroicon-s-share')->form([
                        Select::make('user_ids')
                            ->label('Share with')
                            ->multiple()
                            ->options(User::all()->pluck('name', 'id')->toArray())
                            ->searchable(['name', 'email']),
                        TextInput::make('email')
                            ->label('Send invitation to email')
                            ->email(),
                        DateTimePicker::make('expires_at')
                            ->label('Expire date?')
                            ->nullable(),
                    ])->action(function (array $data, Collection $records): void {if (count($data['user_ids']) > 0) {
                                foreach ($records as $record) {
                                    foreach ($data['user_ids'] as $user_id) {
                                        $record->sharedTo()->syncWithoutDetaching([$user_id => ['expires_at' => $data['expires_at'], 'created_at' => now()]]);
                                    }
                                }
                            }if (! empty($data['email'])) {
                                foreach ($records as $record) {
                                    DB::table('documents_users')->insert(['document_id' => $record->id, 'email' => $data['email'], 'expires_at' => $data['expires_at'], 'created_at' => now()]);
                                }
                            }}),
                ]),
            ]);
    }
}
