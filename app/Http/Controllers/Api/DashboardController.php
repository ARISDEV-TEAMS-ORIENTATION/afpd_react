<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Evenement;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function overview(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $request->query('month', now()->format('Y-m'));
        [$start, $end] = $this->monthRange($month);

        $cotisationsMois = Cotisation::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('date_paiement', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('date_paiement')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->sum('montant');

        return response()->json([
            'month' => $month,
            'adherentes_actives' => User::query()->where('statut', 'actif')->count(),
            'cotisations_du_mois' => (float) $cotisationsMois,
            'evenements_a_venir' => Evenement::query()
                ->where('statut', Evenement::STATUT_ACTIF)
                ->where('date_debut', '>=', now())
                ->count(),
            'notifications_non_lues' => Notification::query()
                ->where('user_id', $request->user()->id)
                ->where('lu', false)
                ->count(),
            'retards_cotisation' => $this->lateUsersCount($month),
        ]);
    }

    public function cotisationsMonthly(Request $request)
    {
        $months = (int) $request->query('months', 6);
        $months = max(1, min(24, $months));

        $start = now()->startOfMonth()->subMonths($months - 1);

        $items = Cotisation::query()
            ->where(function ($query) use ($start) {
                $query->where('date_paiement', '>=', $start)
                    ->orWhere(function ($fallback) use ($start) {
                        $fallback->whereNull('date_paiement')
                            ->where('created_at', '>=', $start);
                    });
            })
            ->get(['montant', 'date_paiement', 'created_at']);

        $bucket = [];
        foreach ($items as $item) {
            $monthKey = Carbon::parse($item->date_paiement ?? $item->created_at)->format('Y-m');
            $bucket[$monthKey] = ($bucket[$monthKey] ?? 0) + (float) $item->montant;
        }

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m = $start->copy()->addMonths($i)->format('Y-m');
            $series[] = [
                'month' => $m,
                'total' => round((float) ($bucket[$m] ?? 0), 2),
            ];
        }

        return response()->json($series);
    }

    public function upcomingEvents(Request $request)
    {
        $limit = max(1, min(50, (int) $request->query('limit', 10)));

        return response()->json(
            Evenement::query()
                ->with('responsable')
                ->where('statut', Evenement::STATUT_ACTIF)
                ->where('date_debut', '>=', now())
                ->orderBy('date_debut')
                ->limit($limit)
                ->get()
        );
    }

    public function alerts(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $request->query('month', now()->format('Y-m'));

        $lateUserIds = $this->lateUserIds($month);

        return response()->json([
            'month' => $month,
            'late_users_count' => count($lateUserIds),
            'pending_users_count' => User::query()->where('statut', 'pending')->count(),
            'pending_events_count' => Evenement::query()->where('statut', Evenement::STATUT_PENDING)->count(),
            'unread_notifications_count' => Notification::query()
                ->where('user_id', $request->user()->id)
                ->where('lu', false)
                ->count(),
        ]);
    }

    private function monthRange(string $month): array
    {
        $parsed = Carbon::createFromFormat('Y-m', $month);

        return [
            $parsed->copy()->startOfMonth(),
            $parsed->copy()->endOfMonth(),
        ];
    }

    private function lateUserIds(string $month): array
    {
        $paidUserIds = Cotisation::query()
            ->where('periode', $month)
            ->where('statut_paiement', 'paye')
            ->pluck('user_id')
            ->all();

        return User::query()
            ->where('statut', 'actif')
            ->whereNotIn('id', $paidUserIds)
            ->pluck('id')
            ->all();
    }

    private function lateUsersCount(string $month): int
    {
        return count($this->lateUserIds($month));
    }
}
