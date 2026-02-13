<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvenementController extends Controller
{
    public function pendingForAdmin()
    {
        return Evenement::with('responsable')
            ->where('statut', Evenement::STATUT_PENDING)
            ->latest()
            ->get();
    }

    public function index(Request $request)
    {
        if ($this->isCommunityManager($request)) {
            return Evenement::with('responsable')->latest()->get();
        }

        return Evenement::with('responsable')
            ->where('statut', Evenement::STATUT_ACTIF)
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titre' => 'required|string',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string',
            'image' => 'required|file|image|max:5120',
            'id_responsable' => 'nullable|exists:users,id'
        ]);

        $responsableId = $request->user()?->id ?? ($validated['id_responsable'] ?? null);

        if (!$responsableId) {
            return response()->json([
                'message' => 'Responsable manquant'
            ], 422);
        }

        $imagePath = $request->file('image')->store('evenements', 'public');

        $event = Evenement::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'] ?? null,
            'lieu' => $validated['lieu'] ?? null,
            'image' => $imagePath,
            'id_responsable' => $responsableId,
            'statut' => Evenement::STATUT_PENDING
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, $id)
    {
        $event = Evenement::with('responsable')->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Événement introuvable ou en attente de validation'
            ], 404);
        }

        if ($this->isCommunityManager($request)) {
            return $event;
        }

        if ($event->statut !== Evenement::STATUT_ACTIF) {
            return response()->json([
                'message' => 'Événement introuvable ou en attente de validation'
            ], 404);
        }

        return $event;
    }

    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        $validated = $request->validate([
            'titre' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'lieu' => 'nullable|string',
            'image' => 'sometimes|required|file|image|max:5120',
            'id_responsable' => 'nullable|exists:users,id'
        ]);

        if ($request->hasFile('image')) {
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }

            $validated['image'] = $request->file('image')->store('evenements', 'public');
        }

        $event->update($validated);

        return $event;
    }

    public function destroy($id)
    {
        $event = Evenement::findOrFail($id);

        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return response()->json([
            'message' => 'Événement supprimé'
        ]);
    }

    private function isCommunityManager(Request $request): bool
    {
        $user = $request->user();
        return $user?->role?->nom_role === 'CommunityManager';
    }
}
