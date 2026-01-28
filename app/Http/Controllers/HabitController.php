<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Habit;
use Illuminate\Support\Facades\Auth;

class HabitController extends Controller
{
    public function index()
    {
        $habits = Habit::where('user_id', Auth::id())->get();
        return view('habits', compact('habits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        Habit::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'progress' => 0,
        ]);
        return redirect()->back();
    }
}
