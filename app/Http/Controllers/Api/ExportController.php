<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Evenement;
use App\Models\Export;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function usersCsv(Request $request)
    {
        $query = User::query()->with('role');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', (int) $request->query('role_id'));
        }

        $users = $query->orderBy('nom')->get();

        $rows = [];
        foreach ($users as $user) {
            $rows[] = [
                $user->id,
                $user->nom,
                $user->prenom,
                $user->email,
                $user->telephone,
                $user->statut,
                $user->role?->nom_role,
                optional($user->created_at)->toDateTimeString(),
            ];
        }

        $path = $this->storeCsv(
            'users',
            ['id', 'nom', 'prenom', 'email', 'telephone', 'statut', 'role', 'created_at'],
            $rows,
            $request
        );

        return Storage::disk('local')->download($path, basename($path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function cotisationsCsv(Request $request)
    {
        $query = Cotisation::query()->with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->query('user_id'));
        }

        if ($request->filled('status')) {
            $query->where('statut_paiement', $request->query('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date_paiement', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date_paiement', '<=', $request->query('to'));
        }

        $cotisations = $query->latest()->get();

        $rows = [];
        foreach ($cotisations as $cotisation) {
            $rows[] = [
                $cotisation->id,
                $cotisation->user_id,
                $cotisation->user?->nom,
                $cotisation->montant,
                $cotisation->periode,
                $cotisation->statut_paiement,
                $cotisation->mode_paiement,
                optional($cotisation->date_paiement)->toDateTimeString(),
                $cotisation->reference,
            ];
        }

        $path = $this->storeCsv(
            'cotisations',
            ['id', 'user_id', 'nom_user', 'montant', 'periode', 'statut', 'mode', 'date_paiement', 'reference'],
            $rows,
            $request
        );

        return Storage::disk('local')->download($path, basename($path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function evenementsCsv(Request $request)
    {
        $query = Evenement::query()->with('responsable');

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('from')) {
            $query->whereDate('date_debut', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date_debut', '<=', $request->query('to'));
        }

        $events = $query->orderBy('date_debut')->get();

        $rows = [];
        foreach ($events as $event) {
            $rows[] = [
                $event->id,
                $event->titre,
                $event->statut,
                optional($event->date_debut)->toDateTimeString(),
                optional($event->date_fin)->toDateTimeString(),
                $event->lieu,
                $event->id_responsable,
                $event->responsable?->nom,
            ];
        }

        $path = $this->storeCsv(
            'evenements',
            ['id', 'titre', 'statut', 'date_debut', 'date_fin', 'lieu', 'responsable_id', 'responsable_nom'],
            $rows,
            $request
        );

        return Storage::disk('local')->download($path, basename($path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function storeCsv(string $type, array $headers, array $rows, Request $request): string
    {
        $path = sprintf('exports/%s-%s.csv', $type, now()->format('Ymd-His'));

        Storage::disk('local')->put($path, $this->toCsv($headers, $rows));

        Export::create([
            'type_export' => $type,
            'format' => 'csv',
            'filtres' => $request->query(),
            'file_path' => $path,
            'generated_by' => $request->user()?->id,
            'generated_at' => now(),
        ]);

        return $path;
    }

    private function toCsv(array $headers, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        fputcsv($stream, $headers);

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return (string) $csv;
    }
}
