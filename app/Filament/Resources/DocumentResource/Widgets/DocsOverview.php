<?php

namespace App\Filament\Resources\DocumentResource\Widgets;

use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $myDocs = Auth::user()->documents()->count();
        $sharedDocs = Auth::user()->sharedDocuments->count();
        $allDocs = $myDocs + $sharedDocs;
        $invitations = Auth::user()->pendingShareInvitations->count();

        return [
            Stat::make('All documents', $allDocs)->color('success'),
            Stat::make('My documents', $myDocs)->color('success'),
            Stat::make('Shared with me', $sharedDocs)->color('success'),
            Stat::make('Invitationer', $invitations)->color('success'),
            Stat::make('Document sharings that expire within 24 days', DB::table('documents_users')->where('user_id', Auth::id())->where('expires_at', '<=', Carbon::now()->addDays(14))->count())->color('warning')
                ->descriptionIcon('heroicon-s-trending-up'),

        ];
    }
}
