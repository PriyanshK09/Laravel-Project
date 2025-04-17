<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SignalController extends Controller
{
    public function sendSignal(Request $request)
    {
        $to = $request->to;
        $data = $request->data;

        $signals = Cache::get("signals_$to", []);
        $signals[] = [
            'from' => auth()->id(),
            'data' => $data
        ];

        Cache::put("signals_$to", $signals, now()->addMinutes(10));

        return response()->json(['status' => 'queued']);
    }

    public function receiveSignal(Request $request)
    {
        $userId = auth()->id();
        $signals = Cache::pull("signals_$userId", []);

        return response()->json($signals);
    }
}
