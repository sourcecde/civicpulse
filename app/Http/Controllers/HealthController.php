<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            $databaseStatus = 'ok';
        } catch (Throwable) {
            $databaseStatus = 'unreachable';
        }

        $status = $databaseStatus === 'ok' ? 'ok' : 'error';

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'database' => $databaseStatus,
        ], $status === 'ok' ? 200 : 503);
    }
}
