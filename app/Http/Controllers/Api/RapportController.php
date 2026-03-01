<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotisation;
use App\Models\Evenement;
use App\Models\Rapport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        $query = Rapport::query();

        if ($request->filled('periode')) {
            $query->where('periode', $request->query('periode'));
        }

        if ($request->filled('type_rapport')) {
            $query->where('type_rapport', $request->query('type_rapport'));
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_rapport' => 'required|string',
            'periode' => 'required|string',
            'chemin_fichier' => 'nullable|string',
            'format' => 'nullable|string',
            'statut_generation' => 'nullable|string',
            'generated_at' => 'nullable|date',
            'id_createur' => 'nullable|exists:users,id',
        ]);

        $createurId = $request->user()?->id ?? ($validated['id_createur'] ?? null);

        $rapport = Rapport::create([
            'type_rapport' => $validated['type_rapport'],
            'periode' => $validated['periode'],
            'chemin_fichier' => $validated['chemin_fichier'] ?? null,
            'format' => $validated['format'] ?? null,
            'statut_generation' => $validated['statut_generation'] ?? 'termine',
            'generated_at' => $validated['generated_at'] ?? now(),
            'id_createur' => $createurId,
        ]);

        return response()->json($rapport, 201);
    }

    public function show($id)
    {
        return Rapport::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $rapport = Rapport::findOrFail($id);

        $validated = $request->validate([
            'type_rapport' => 'sometimes|required|string',
            'periode' => 'sometimes|required|string',
            'chemin_fichier' => 'nullable|string',
            'format' => 'nullable|string',
            'statut_generation' => 'nullable|string',
            'generated_at' => 'nullable|date',
        ]);

        $rapport->update($validated);

        return $rapport;
    }

    public function destroy($id)
    {
        $rapport = Rapport::findOrFail($id);

        if ($rapport->chemin_fichier && Storage::disk('local')->exists($rapport->chemin_fichier)) {
            Storage::disk('local')->delete($rapport->chemin_fichier);
        }

        $rapport->delete();

        return response()->json([
            'message' => 'Rapport supprimé',
        ]);
    }

    public function generateMonthly(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'format' => ['nullable', Rule::in(['csv', 'pdf'])],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $format = $validated['format'] ?? 'csv';

        [$start, $end] = $this->monthRange($month);

        $activeUsers = User::query()->where('statut', 'actif')->count();
        $newUsers = User::query()->whereBetween('created_at', [$start, $end])->count();
        $cotisationsAmount = Cotisation::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('date_paiement', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('date_paiement')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->sum('montant');
        $cotisationsCount = Cotisation::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('date_paiement', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback->whereNull('date_paiement')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->count();
        $eventsCount = Evenement::query()->whereBetween('date_debut', [$start, $end])->count();

        $metrics = [
            ['period', $month],
            ['active_users', $activeUsers],
            ['new_users', $newUsers],
            ['cotisations_count', $cotisationsCount],
            ['cotisations_amount', round((float) $cotisationsAmount, 2)],
            ['events_count', $eventsCount],
        ];

        $path = sprintf('reports/rapport-mensuel-%s-%s.%s', $month, now()->format('Ymd-His'), $format);

        if ($format === 'csv') {
            $content = $this->toCsv(['metric', 'value'], $metrics);
        } else {
            $lines = [
                'AFPD - Rapport mensuel',
                'Periode: ' . $month,
                'Genere le: ' . now()->format('Y-m-d H:i:s'),
                '',
            ];

            foreach ($metrics as $metric) {
                $lines[] = $metric[0] . ': ' . $metric[1];
            }

            $content = $this->buildSimplePdf($lines);
        }

        Storage::disk('local')->put($path, $content);

        $rapport = Rapport::create([
            'type_rapport' => 'mensuel',
            'periode' => $month,
            'chemin_fichier' => $path,
            'format' => $format,
            'statut_generation' => 'termine',
            'generated_at' => now(),
            'id_createur' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Rapport mensuel généré',
            'rapport' => $rapport,
        ], 201);
    }

    public function download(int $id)
    {
        $rapport = Rapport::findOrFail($id);

        if (!$rapport->chemin_fichier || !Storage::disk('local')->exists($rapport->chemin_fichier)) {
            return response()->json([
                'message' => 'Fichier de rapport introuvable',
            ], 404);
        }

        return Storage::disk('local')->download(
            $rapport->chemin_fichier,
            basename($rapport->chemin_fichier)
        );
    }

    private function monthRange(string $month): array
    {
        $parsed = Carbon::createFromFormat('Y-m', $month);

        return [
            $parsed->copy()->startOfMonth(),
            $parsed->copy()->endOfMonth(),
        ];
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
