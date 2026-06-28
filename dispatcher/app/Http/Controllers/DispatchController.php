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
            'idempotency_key' => 'required|string',
        ]);

        // basic sql to find the first row in the table with the same idempotency key
        $existingDispatch = Dispatch::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingDispatch) {
            return response()->json(['message' => 'Duplicate request'], 200); // OK, move on
        }

        $dispatch = Dispatch::create([
            'type' => $validated['type'],
            'payload' => $validated['payload'],
            // if we gen new idempotency key for each request at server it makes each request unique
            // this means if user clicks on the button multiple times it will register as multiple requests
            // so we can create idempotency key at client side and check if it is unique to prevent duplicate requests
            'idempotency_key' => $validated['idempotency_key'],
        ]);
        return response()->json(['message' => 'Dispatch created successfully', 'data' => $dispatch], 201);
    }
}
