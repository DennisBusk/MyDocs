<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('All documents')->url(route('filament.admin.resources.documents.index'))->extraAttributes(['class' => '!border-1 !shadow !text-gray-800 bg-white !hover:!shadow-xl', 'wire:navigate.hover' => '']),
            Action::make('My documents')->url(route('filament.admin.resources.documents.my_docs'))->extraAttributes([
                'class' => '!border-1 !shadow !text-gray-800 bg-white !hover:!shadow-xl',
                'wire:navigate.hover' => '',
            ]),
            Action::make('Shared with me')->url(route('filament.admin.resources.documents.shared_with_me'))->extraAttributes([
                'class' => '!border-1 !shadow !text-gray-800 bg-white !hover:!shadow-xl',
                'wire:navigate.hover' => '',
            ]),
            //            Action::make('Pending invitations')->url(route('filament.admin.resources.documents.pending_invitations'))->extraAttributes([
            //                'class'               => '!border-1 !shadow !text-white bg-custom-200 !hover:!shadow-xl',
            //                'wire:navigate.hover' => '',
            //            ]),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')->default(fn () => auth()->id())->required(),
                Forms\Components\FileUpload::make('url')
                    ->directory(Auth::id().'/'.now()->format('Y-m-d'))
                    ->visibility('public')
                    ->openable()
                    ->previewable()->downloadable()
                    ->getUploadedFileNameForStorageUsing(
                        function (TemporaryUploadedFile $file): string {
                            $name = Str::slug(pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
                            $i = 1;
                            while (Storage::exists(Auth::id().'/'.now()->format('Y-m-d').'/'.$name)) {
                                $name = $i.'-'.Str::slug(pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
                                $i++;
                            }

                            return $name;
                        })
                    ->required()->live()->afterStateUpdated(function (
                        Get $get,
                        Set $set,
                        ?string $state
                    ) {
                        $files = [];
                        foreach ($get('url') as $key => $value) {
                            $files[] = $value;
                        }
                        $file = $files[0];
                        $set('name', $file->getClientOriginalName());
                        $set('extension', $file->getClientOriginalExtension());
                        $set('size', $file->getSize());
                    }),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)->readOnly(),
                //                Forms\Components\TextInput::make('url')
                //                    ->required()
                //                    ->maxLength(255),
                Forms\Components\TextInput::make('size')
                    ->required()
                    ->maxLength(255)->readOnly(),
                Forms\Components\TextInput::make('extension')
                    ->required()
                    ->maxLength(255)->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Document::whereIn('id', array_merge(Auth::user()->sharedDocuments->pluck('id')->toArray(), Auth::user()->documents()->pluck('id')->toArray())))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Owner name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('File name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')->label('File size'),
                Tables\Columns\TextColumn::make('extension')->label('File type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('Show')->icon('heroicon-s-eye')->url(fn (Document $record): string => Storage::url($record->url))->extraAttributes(['target' => '_blank']),
                Tables\Actions\Action::make('Download')->icon('heroicon-m-cloud-arrow-down')->color('success')->url(fn (Document $record): string => route('documents.download', ['document' => $record->id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'my_docs' => Pages\MyDocumentsPage::route('/my_docs'),
            'shared_with_me' => Pages\SharedWithMePage::route('/shared_with_me'),
            'pending_invitations' => Pages\PendingInvitationsPage::route('/pending_invitations'),
            'create' => Pages\CreateDocument::route('/create'),
            'view' => Pages\ViewDocument::route('/{record}'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
    private function getFileName($file){
return Str::basename($file->getClientOriginalName()).'.'.$file->getClientOriginalExtension();
    }
}
