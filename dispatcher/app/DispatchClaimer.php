<?php

namespace App;

use App\Models\Dispatch;
// DB facade helps write raw SQL queries without ORM like ELoquent, its built in
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DispatchClaimer
{
    // this will be the actual func called for claiming in the command
    public function claim(): ?Dispatch // nullable return-->could be null or a Dispatch
    {
        // this is auto transaction, could do manual with try/cathc blokc using DB::beginTransaction();
        // while using auto method, rollback and commit is handled automatically
       return DB::transaction(function () {// takes in closure(anon func) & thru use keyword can access external vars --> function () use $stuff {}
           $claimedDispatch = Dispatch::where('status', 'pending')->where('available_at', '<=', now())
           ->lock('FOR UPDATE SKIP LOCKED') //  does both; lock for update and skip locked
           ->first(); 
           // pretty much what the name ssays, locks the selected row for update 
           // so that no other proc or db session can modify it till this current transaction is complete

           if (!$claimedDispatch) {
               return null;
           }
           
            $claimedDispatch->update([
                'status' => 'processing',
                'claimed_at' => now(),
                'claimed_by' => gethostname(),
            ]);

           return $claimedDispatch;
       });
    }
}