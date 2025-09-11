<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // public function register(Request $request)
    // {
    //     $data = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'contact_number' => 'nullable|string|max:20',
    //         'email' => 'nullable|email|unique:users,email',
    //         'password' => 'required|string|min:6|confirmed',
    //         'role' => ['required', Rule::in(['user','worker','employer','dole','admin'])],
    //     ]);

    //     $user = User::create([
    //         'name' => $data['name'],
    //         'contact_number' => $data['contact_number'] ?? null,
    //         'email' => $data['email'] ?? null,
    //         'password' => Hash::make($data['password']),
    //         'role' => $data['role'],
    //     ]);

    //     $token = $user->createToken('api-token')->plainTextToken;

    //     return response()->json([
    //         'user' => $user,
    //         'token' => $token
    //     ], 201);
    // }
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:worker,employer',
            'skills' => 'nullable|string',
            'experience' => 'nullable|string',
            'business_name' => 'nullable|string',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'contact_number' => $validated['contact_number'] ?? null,
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'skills' => $validated['skills'] ?? null,
            'experience' => $validated['experience'] ?? null,
            'business_name' => $validated['business_name'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'address' => $validated['address'] ?? null,
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
        ], 201);
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'name' => ['The provided credentials are incorrect.'],
            ]);
        }

        if($user && $user->status !== "active"){
            throw ValidationException::withMessages([
                'name' => ['Inactive Account.'],
            ]);
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
