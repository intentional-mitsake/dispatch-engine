<?php

namespace App\Http\Controllers;

use App\Models\Dispatch;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function req(Request $request) {  // Post request
        $validated = $request->validate([ // validate incoming request-->for things like sql injection
            'type' => 'required|string',
            'payload' => 'required|array',
        ]);

        $dispatch = Dispatch::create(
            'type' => $validated['type'],
            'payload' => $validated['payload'],
            'idempotency_key' => Str::uuid(), // generate unique id
            'status' => 'pending',
            'available_at' => now(),
        );
        return response()->json(['message' => 'Dispatch created successfully', 'data' => $dispatch], 201);
    }
}
