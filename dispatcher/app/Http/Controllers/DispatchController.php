<?php

namespace App\Http\Controllers;

use App\Models\Dispatch;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function store(Request $request) {  // Post request
    // validate() automatically returns a 422 response if validation fails, so we don't need to handle that manually
        $validated = $request->validate([ // validate incoming request-->for things like sql injection
            'type' => 'required|string',
            'payload' => 'required|array',
            'payload.amount' => 'required_if:type,payment|numeric|min:0.01', // if type is payment then amount is required and must be a positive number
            'payload.customer_id' => 'required_if:type,payment|string', // if type is payment then customer_id is required and must be a string
            'idempotency_key' => 'required|string',
        ]);
        Log::info("Dispatch request received: " . json_encode($validated));

        // basic sql to find the first row in the table with the same idempotency key
        $existingDispatch = Dispatch::where('idempotency_key', $validated['idempotency_key'])->first();
        if ($existingDispatch) {
            if($existingDispatch->type !== $validated['type'] || $existingDispatch->payload !== $validated['payload']) {
                Log::error("Idempotency key conflict: Existing dispatch has different type or payload for idempotency key: " . $validated['idempotency_key']);
                return response()->json(['message' => 'Idempotency key conflict'], 409); // Conflict so 409 error code is returned
            }
            Log::info("Duplicate dispatch request received with idempotency key: " . $validated['idempotency_key']);
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
        Log::info("Dispatch created with ID: {$dispatch->id} and idempotency key: {$dispatch->idempotency_key}");
        return response()->json(['message' => 'Dispatch created successfully', 'data' => $dispatch], 201);
    }

    public function show(Dispatch $dispatch) {
        // we have {dispatch} in the route, laravel auto looks for method with parameter with the same name
        // so this func is called, $dispatch is an Eloquent model so laravel auto queries the database with the given value
        // it doe select * from dispatches where id = $dispatch as id is primary key
        // unless u tell it to not look for primary key, it will look for id auto
        return response()->json(['message' => 'Dispatch retrieved successfully', 'data' => $dispatch], 200);
    }
}
