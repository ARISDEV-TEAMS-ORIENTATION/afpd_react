<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Models\InscriptionEvenement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    public function upcoming()
    {
        return response()->json(
            Evenement::query()
                ->with('responsable')
                ->where('statut', Evenement::STATUT_ACTIF)
                ->where('date_debut', '>=', now())
                ->orderBy('date_debut')
                ->get()
        );
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
            'id_responsable' => 'nullable|exists:users,id',
        ]);

        $responsableId = $request->user()?->id ?? ($validated['id_responsable'] ?? null);

        if (!$responsableId) {
            return response()->json([
                'message' => 'Responsable manquant',
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
            'statut' => Evenement::STATUT_PENDING,
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, $id)
    {
        $event = Evenement::with('responsable')->find($id);

        if (!$event) {
            return response()->json([
                'message' => 'Événement introuvable ou en attente de validation',
            ], 404);
        }

        if ($this->isCommunityManager($request)) {
            return $event;
        }

        if ($event->statut !== Evenement::STATUT_ACTIF) {
            return response()->json([
                'message' => 'Événement introuvable ou en attente de validation',
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
            'id_responsable' => 'nullable|exists:users,id',
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
            'message' => 'Événement supprimé',
        ]);
    }

    public function subscribe(Request $request, int $id)
    {
        $event = Evenement::findOrFail($id);

        if ($event->statut !== Evenement::STATUT_ACTIF && !$this->isCommunityManager($request)) {
            return response()->json([
                'message' => 'Événement non disponible pour inscription',
            ], 403);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
        ]);

        $targetUserId = $validated['user_id'] ?? $request->user()?->id;

        if (!$targetUserId) {
            return response()->json([
                'message' => 'Utilisateur cible manquant',
            ], 422);
        }

        if ((int) $targetUserId !== (int) $request->user()?->id && !$this->canManageParticipants($request)) {
            return response()->json([
                'message' => 'Action non autorisée pour cet utilisateur',
            ], 403);
        }

        $inscription = InscriptionEvenement::firstOrNew([
            'user_id' => $targetUserId,
            'evenement_id' => $event->id,
        ]);

        $isNew = !$inscription->exists;

        $inscription->fill([
            'date_inscription' => $inscription->date_inscription ?? now(),
            'statut_inscription' => 'inscrite',
        ]);
        $inscription->save();

        return response()->json($inscription, $isNew ? 201 : 200);
    }

    public function unsubscribe(Request $request, int $id, int $userId)
    {
        if ((int) $request->user()?->id !== $userId && !$this->canManageParticipants($request)) {
            return response()->json([
                'message' => 'Action non autorisée pour cet utilisateur',
            ], 403);
        }

        $deleted = InscriptionEvenement::query()
            ->where('evenement_id', $id)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Inscription introuvable',
            ], 404);
        }

        return response()->json([
            'message' => 'Inscription supprimée',
        ]);
    }

    public function participants(int $id)
    {
        $event = Evenement::with(['participants.role'])->findOrFail($id);

        return response()->json($event->participants);
    }

    public function markPresence(Request $request, int $id, int $userId)
    {
        $event = Evenement::findOrFail($id);

        $validated = $request->validate([
            'presence' => 'required|boolean',
        ]);

        $inscription = InscriptionEvenement::firstOrNew([
            'evenement_id' => $event->id,
            'user_id' => $userId,
        ]);

        $inscription->fill([
            'date_inscription' => $inscription->date_inscription ?? now(),
            'presence' => $validated['presence'],
            'date_presence' => $validated['presence'] ? now() : null,
            'statut_inscription' => $validated['presence'] ? 'presente' : 'absente',
        ]);

        $inscription->save();

        return response()->json($inscription);
    }

    public function updateStatus(Request $request, int $id)
    {
        $event = Evenement::findOrFail($id);

        $validated = $request->validate([
            'statut' => ['required', Rule::in([
                Evenement::STATUT_PENDING,
                Evenement::STATUT_ACTIF,
                Evenement::STATUT_REFUSE,
            ])],
        ]);

        $event->update([
            'statut' => $validated['statut'],
        ]);

        return response()->json($event->fresh());
    }

    private function isCommunityManager(Request $request): bool
    {
        return in_array(
            $request->user()?->role?->nom_role,
            ['CommunityManager', 'Presidente'],
            true
        );
    }

    private function canManageParticipants(Request $request): bool
    {
        return in_array(
            $request->user()?->role?->nom_role,
            ['Presidente', 'CommunityManager', 'Responsable'],
            true
        );
    }
}
