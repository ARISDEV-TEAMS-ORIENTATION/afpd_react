<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CotisationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $this->filteredQuery($request)
                ->latest()
                ->get()
        );
    }

    public function show($id)
    {
        return response()->json(
            Cotisation::with('user')->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'periode' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'statut_paiement' => ['nullable', Rule::in(['en_attente', 'paye', 'partiel', 'annule'])],
            'mode_paiement' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255|unique:cotisations,reference',
        ]);

        $paymentDate = isset($data['date_paiement'])
            ? Carbon::parse($data['date_paiement'])
            : now();

        $data['date_paiement'] = $paymentDate;
        $data['periode'] = $data['periode'] ?? $paymentDate->format('Y-m');
        $data['statut_paiement'] = $data['statut_paiement'] ?? 'paye';
        $data['reference'] = $data['reference'] ?? $this->generateReference();

        $cotisation = Cotisation::create($data);

        return response()->json($cotisation, 201);
    }

    public function update(Request $request, $id)
    {
        $cotisation = Cotisation::findOrFail($id);

        $data = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'montant' => 'sometimes|numeric|min:0',
            'date_paiement' => 'nullable|date',
            'periode' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'statut_paiement' => ['sometimes', Rule::in(['en_attente', 'paye', 'partiel', 'annule'])],
            'mode_paiement' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255|unique:cotisations,reference,' . $cotisation->id,
            'recu_path' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('date_paiement', $data) && !array_key_exists('periode', $data) && !empty($data['date_paiement'])) {
            $data['periode'] = Carbon::parse($data['date_paiement'])->format('Y-m');
        }

        $cotisation->update($data);

        return response()->json($cotisation->fresh());
    }

    public function destroy($id)
    {
        $cotisation = Cotisation::findOrFail($id);
        $cotisation->delete();

        return response()->json(null, 204);
    }

    public function summary(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $request->query('month', now()->format('Y-m'));
        [$start, $end] = $this->monthRange($month);

        $query = Cotisation::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('date_paiement', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('date_paiement')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            });

        if ($request->filled('status')) {
            $query->where('statut_paiement', $request->query('status'));
        }

        $totalAmount = (float) (clone $query)->sum('montant');
        $totalCount = (clone $query)->count();
        $byStatus = (clone $query)
            ->selectRaw('statut_paiement, COUNT(*) as total_count, COALESCE(SUM(montant), 0) as total_amount')
            ->groupBy('statut_paiement')
            ->get();

        return response()->json([
            'month' => $month,
            'total_count' => $totalCount,
            'total_amount' => round($totalAmount, 2),
            'by_status' => $byStatus,
        ]);
    }

    public function late(Request $request)
    {
        $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        $month = $request->query('month', now()->format('Y-m'));

        $paidUserIds = Cotisation::query()
            ->where('periode', $month)
            ->where('statut_paiement', 'paye')
            ->pluck('user_id')
            ->all();

        $users = User::query()
            ->with('role')
            ->where('statut', 'actif')
            ->whereNotIn('id', $paidUserIds)
            ->orderBy('nom')
            ->get();

        return response()->json([
            'month' => $month,
            'total' => $users->count(),
            'users' => $users,
        ]);
    }

    public function generateReceipt(int $id)
    {
        $cotisation = Cotisation::with('user')->findOrFail($id);

        $reference = $cotisation->reference ?: $this->generateReference();
        $filename = sprintf('receipt-%d-%s.txt', $cotisation->id, now()->format('Ymd-His'));
        $path = 'receipts/' . $filename;

        $content = implode(PHP_EOL, [
            'AFPD - Reçu de cotisation',
            'Référence: ' . $reference,
            'Cotisation ID: ' . $cotisation->id,
            'Membre: ' . ($cotisation->user?->nom ?? 'N/A'),
            'Montant: ' . number_format((float) $cotisation->montant, 2, ',', ' ') . ' FCFA',
            'Période: ' . ($cotisation->periode ?? 'N/A'),
            'Date paiement: ' . optional($cotisation->date_paiement)->format('Y-m-d H:i:s'),
            'Statut: ' . $cotisation->statut_paiement,
            'Généré le: ' . now()->format('Y-m-d H:i:s'),
        ]) . PHP_EOL;

        Storage::disk('local')->put($path, $content);

        $cotisation->update([
            'reference' => $reference,
            'recu_path' => $path,
        ]);

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function exportCsv(Request $request)
    {
        $cotisations = $this->filteredQuery($request)
            ->latest()
            ->get();

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

        $csv = $this->toCsv([
            'id',
            'user_id',
            'nom_user',
            'montant',
            'periode',
            'statut',
            'mode',
            'date_paiement',
            'reference',
        ], $rows);

        $path = 'exports/cotisations-' . now()->format('Ymd-His') . '.csv';
        Storage::disk('local')->put($path, $csv);

        return Storage::disk('local')->download($path, basename($path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $cotisations = $this->filteredQuery($request)
            ->latest()
            ->limit(100)
            ->get();

        $lines = [
            'AFPD - Export cotisations',
            'Date generation: ' . now()->format('Y-m-d H:i:s'),
            '',
        ];

        foreach ($cotisations as $cotisation) {
            $lines[] = sprintf(
                '#%d | user:%d | montant:%s | periode:%s | statut:%s',
                $cotisation->id,
                $cotisation->user_id,
                number_format((float) $cotisation->montant, 2, '.', ''),
                $cotisation->periode ?? 'N/A',
                $cotisation->statut_paiement
            );
        }

        $pdf = $this->buildSimplePdf($lines);

        $path = 'exports/cotisations-' . now()->format('Ymd-His') . '.pdf';
        Storage::disk('local')->put($path, $pdf);

        return Storage::disk('local')->download($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function filteredQuery(Request $request)
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

        return $query;
    }

    private function monthRange(string $month): array
    {
        $parsed = Carbon::createFromFormat('Y-m', $month);

        return [
            $parsed->copy()->startOfMonth(),
            $parsed->copy()->endOfMonth(),
        ];
    }

    private function generateReference(): string
    {
        return sprintf(
            'COT-%s-%s',
            now()->format('YmdHis'),
            str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT)
        );
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

    private function buildSimplePdf(array $lines): string
    {
        $lines = array_slice($lines, 0, 45);

        $contentLines = [
            'BT',
            '/F1 11 Tf',
            '45 780 Td',
        ];

        $first = true;
        foreach ($lines as $line) {
            if (!$first) {
                $contentLines[] = '0 -16 Td';
            }
            $contentLines[] = '(' . $this->pdfEscape($line) . ') Tj';
            $first = false;
        }

        $contentLines[] = 'ET';
        $stream = implode("\n", $contentLines) . "\n";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream\nendobj\n";
        $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value
        );
    }
}
