<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = [
            ['id' => 1, 'name' => 'Admin User', 'email' => 'admin@warelog.com', 'role' => 'Administrator'],
            ['id' => 2, 'name' => 'Juan Dela Cruz', 'email' => 'juan@warelog.com', 'role' => 'Staff'],
            ['id' => 3, 'name' => 'Maria Santos', 'email' => 'maria@warelog.com', 'role' => 'Encoder'],
        ];
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('users.index')->with('success', 'User added successfully!');
    }
}