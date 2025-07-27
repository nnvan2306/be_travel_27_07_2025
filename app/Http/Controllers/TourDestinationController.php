<?php

namespace App\Http\Controllers;

use App\Models\TourDestination;
use Illuminate\Http\Request;

class TourDestinationController extends Controller
{
    public function index()
    {
        return TourDestination::with(['tour', 'destination'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'tour_id' => 'required|integer|exists:tours,tour_id',
            'destination_id' => 'required|integer|exists:destinations,destination_id',
            'order' => 'nullable|integer',
        ]);

        $tourDestination = TourDestination::create($request->all());

        return response()->json($tourDestination, 201);
    }

    public function destroy($id)
    {
        $item = TourDestination::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted']);
    }
}