<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final class MeController extends Controller
{
    public function __invoke(#[CurrentUser] User $user): User
    {
        return $user;
    }
}
