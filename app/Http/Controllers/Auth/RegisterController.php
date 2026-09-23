<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterUserRequest $request): JsonResponse
    {
        $user = new User();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->password = $request->validated('password');
        $user->role = UserRole::CITIZEN;
        $user->save();

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }
}
