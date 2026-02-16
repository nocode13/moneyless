<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;

final class MeController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        $user = $request->user();

        return UserResource::make($user);
    }
}
