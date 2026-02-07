<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use Illuminate\Http\Request;

class EvenementController extends Controller
{
    public function index()
    {
        return Evenement::all();
    }

    public function store(Request $request)
    {
        return Evenement::create($request->all());
    }

    public function show($id)
    {
        return Evenement::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $event = Evenement::findOrFail($id);

        $event->update($request->all());

        return $event;
    }

    public function destroy($id)
    {
        Evenement::destroy($id);

        return response()->json([
            'message' => 'Événement supprimé'
        ]);
    }
}
