<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\OperatorResource;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorController extends Controller
{
    public function index(Request $request): JsonResource
    {
        abort_unless(
            in_array(
                $request->user()->role,
                [UserRole::ADMIN, UserRole::OPERATOR],
                true
            ),
            403
        );

        $operators = User::query()
            ->where('role', UserRole::OPERATOR)
            ->orderBy('name')
            ->get(['id', 'name']);

        return OperatorResource::collection($operators);
    }
}
