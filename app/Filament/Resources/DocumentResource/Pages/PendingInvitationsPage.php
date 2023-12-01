<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Document;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PendingInvitationsPage extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Pending invitations';
    }

    protected static ?string $navigationLabel = 'Pending invitations';

    public static function getNavigationGroup(): ?string
    {
        return 'Documents';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Document::whereIn('id', Auth::user()->pendingShareInvitations->pluck('id')->toArray()))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Owner name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('File name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size')->label('File size')
                    ->searchable(),
                Tables\Columns\TextColumn::make('extension')->label('File type')
                    ->searchable(),
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

                Tables\Actions\Action::make('Accepter')->icon('heroicon-s-hand-thumb-up')->color('success')->action(function(Document $record): string {DB::table('documents_users')->where('email', Auth::user()->email)->where('document_id', $record->id)->update(['email' => null, 'user_id' => Auth::id()]);return redirect()->route('filament.admin.resources.documents.pending_invitations');}),
                Tables\Actions\Action::make('Afslå')->icon('heroicon-s-hand-thumb-down')->color('danger')->action(function (Document $record): string {DB::table('documents_users')->where('email', Auth::user()->email)->where('document_id', $record->id)->delete();return redirect()->route('filament.admin.resources.documents.pending_invitations');}),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('Accepter')->icon('heroicon-s-hand-thumb-up')->action(function (Collection $records) {
                        $records->each(function (Document $record) {
                            DB::table('documents_users')->where('email', Auth::user()->email)->where('document_id', $record->id)->update(['email' => null, 'user_id' => Auth::id()]);
                        });
                        return redirect()->route('filament.admin.resources.documents.pending_invitations');
                    }),
                    BulkAction::make('Afslå')->icon('heroicon-s-hand-thumb-down')->action(function (Collection $records) {
                        $records->each(function (Document $record) {
                            DB::table('documents_users')->where('email', Auth::user()->email)->where('document_id', $record->id)->delete();
                        });
                        return redirect()->route('filament.admin.resources.documents.pending_invitations');
                    }),
                ]),
            ]);
    }
}
