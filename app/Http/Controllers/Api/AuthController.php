<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Setting;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
    // public function register(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'contact_number' => 'nullable|string|max:20',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:6|confirmed',
    //         'role' => 'required|in:worker,employer',
    //         'skills' => 'nullable|string',
    //         'experience' => 'nullable|string',
    //         'business_name' => 'nullable|string',
    //         'lat' => 'nullable|string',
    //         'lng' => 'nullable|string',
    //         'address' => 'nullable|string',
    //     ]);

    //     $user = User::create([
    //         'name' => $validated['name'],
    //         'contact_number' => $validated['contact_number'] ?? null,
    //         'email' => $validated['email'],
    //         'password' => bcrypt($validated['password']),
    //         'role' => $validated['role'],
    //         'skills' => $validated['skills'] ?? null,
    //         'experience' => $validated['experience'] ?? null,
    //         'business_name' => $validated['business_name'] ?? null,
    //         'lat' => $validated['lat'] ?? null,
    //         'lng' => $validated['lng'] ?? null,
    //         'address' => $validated['address'] ?? null,
    //     ]);

    //     $token = $user->createToken('api_token')->plainTextToken;

    //     return response()->json([
    //         'message' => 'User registered successfully',
    //         'user' => $user,
    //         'access_token' => $token,
    //     ], 201);
    // }

    // public function register(Request $request)
    // {
    //     if($request->role === "worker"){
    //         $validated = $request->validate([
    //             'name'           => 'required|string|max:255',
    //             'contact_number' => 'nullable|string|max:20',
    //             'email'          => 'required|email|unique:users,email',
    //             'password'       => 'required|string|min:6|confirmed',
    //             'role'           => 'required|in:worker,employer',
    //             'skills'         => 'required|string',
    //             'experience'     => 'required|string',
    //             'lat'            => 'required|string',
    //             'lng'            => 'required|string',
    //             'location'       => 'required|string',
    //         ]);
    //     } else if ($request->role === "employer"){
    //         $validated = $request->validate([
    //             'name'           => 'required|string|max:255',
    //             'contact_number' => 'nullable|string|max:20',
    //             'email'          => 'required|email|unique:users,email',
    //             'password'       => 'required|string|min:6|confirmed',
    //             'role'           => 'required|in:worker,employer',
    //             'business_name'  => 'required|string',
    //             'lat'            => 'required|string',
    //             'lng'            => 'required|string',
    //             'location'       => 'required|string',
    //         ]);
    //     } else {
    //         $validated = $request->validate([
    //             'role' => 'required',
    //         ]);
    //     }


    //     $locationId = null;

    //     if (!empty($validated['location'])) {
    //         // Split barangay, municipality
    //         [$barangay, $municipality] = array_map('trim', explode(',', $validated['location']));

    //         $location = \App\Models\Location::where('barangay', 'like', $barangay)
    //             ->where('municipality', 'like', $municipality)
    //             ->first();

    //         if ($location) {
    //             $locationId = $location->id;
    //         }
    //     }

    //     $user = User::create([
    //         'name'           => $validated['name'],
    //         'contact_number' => $validated['contact_number'] ?? null,
    //         'email'          => $validated['email'],
    //         'password'       => bcrypt($validated['password']),
    //         'role'           => $validated['role'],
    //         'skills'         => $validated['skills'] ?? null,
    //         'experience'     => $validated['experience'] ?? null,
    //         'business_name'  => $validated['business_name'] ?? null,
    //         'lat'            => $validated['lat'] ?? null,
    //         'lng'            => $validated['lng'] ?? null,
    //         'location_id'    => $locationId,
    //     ]);

    //     $token = $user->createToken('api_token')->plainTextToken;

    //     return response()->json([
    //         'message'      => 'User registered successfully',
    //         'user'         => $user->load('location'),
    //         'access_token' => $token,
    //     ], 201);
    // }

    public function register(Request $request)
    {
        $decodedEducations = json_decode($request->input('educations'), true);

        // Merge them back into the request
        $request->merge([
            'educations' => $decodedEducations,
        ]);

        $baseRules = [
            "first_name" => "required|string|max:100",
            "middle_name" => "required|string|max:100",
            "last_name" => "required|string|max:100",
            "suffix" => "nullable|string|max:20",
            "contact_number" => "required|string|min:11|max:11",
            "email" => "required|email|unique:users,email",
            "password" => "required|confirmed|min:6",
            "role" => ['required', Rule::in(['worker', 'employer'])],
            "has_disability" => "required",
            "disability_specify" => "nullable|string|max:255",
            
            "sex" => "required|string|max:255",
            "religion" => "required|string|max:255",
            "civil_status" => "required|string|max:255",
            "height" => "required|string|max:255",
            
            "location" => "required|string",
            "lat" => "required|string",
            "lng" => "required|string",

            'barangay_clearance_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            'valid_id_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            'selfie_with_id_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
        ];

        $extraRules = [];

        if($request->role == 'worker'){
            $extraRules = [
                "skills" => "required|string",
                "skill_specify" => "nullable|string",
                "experience" => "required|string",
                
                'educations' => 'required|array|min:1',
                'educations.*.level' => 'required|string|max:255',
                'educations.*.school_name' => 'required|string|max:255',
                'educations.*.course' => 'nullable|string|max:255',
                'educations.*.year_graduated' => 'nullable|digits:4',
        
                'certificates' => 'required|array|min:1',
                'certificates.*.title' => 'required|string|max:255',
                'certificates.*.issuing_organization' => 'nullable|string|max:255',
                'certificates.*.date_issued' => 'nullable|date',
                'certificates.*.certificate_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            ];

            if($request->has_disability == true){
                $extraRules = array_merge($extraRules, [
                    "disabilities" => "required|string",
                ]);
            }
        } else if ($request->role == 'employer'){
            $extraRules = [
                "employer_type" => "required|string",
            ];
            
            if($request->employer_type == 'establishment'){
                $extraRules = array_merge($extraRules, [
                    "business_name" => "required|string|max:255",
                    'business_permit_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
                    'bir_certificate_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
                ]);
            }
        }

        $validator = Validator::make($request->all(), array_merge($baseRules, $extraRules));

        $validated = $validator->validate();

        try {
            DB::beginTransaction();

            $uploadFolder = 'uploads/users';
            $fileFields = [
                'barangay_clearance_photo',
                'valid_id_photo',
                'selfie_with_id_photo',
                'business_permit_photo',
                'bir_certificate_photo',
            ];
    
            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store($uploadFolder, 'public');
                    $validated[$field] = $path;
                }
            }
    
            $locationId = null;
    
            if (!empty($validated['location'])) {
                // Split barangay, municipality
                [$barangay, $municipality] = array_map('trim', explode(',', $validated['location']));
    
                $location = \App\Models\Location::where('barangay', 'like', $barangay)
                    ->where('municipality', 'like', $municipality)
                    ->first();
    
                if ($location) {
                    $locationId = $location->id;
                }
            }
    
            $user = User::create([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'last_name' => $validated['last_name'],
                'suffix' => $validated['suffix'] ?? null,
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'],
    
                'sex' => $validated['sex'],
                'religion' => $validated['religion'],
                'civil_status' => $validated['civil_status'],
    
                'height' => $validated['height'],
                
                'location_id' => $locationId,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
    
                'has_disability' => $validated['has_disability'],
                'disabilities' => $validated['disabilities'] ?? null,
                'disability_specify' => $validated['disability_specify'] ?? null,
                
                "skills" => $validated["skills"] ?? null,
                'skill_specify' => $validated['skill_specify'] ?? null,
    
                "experience" => $validated["experience"] ?? null,
                
                'employer_type' => $validated['employer_type'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
    
                // Common required images
                'barangay_clearance_photo' => $validated['barangay_clearance_photo'],
                'valid_id_photo' => $validated['valid_id_photo'],
                'selfie_with_id_photo' => $validated['selfie_with_id_photo'],
    
                // Employer-specific docs
                'business_permit_photo' => $validated['business_permit_photo'] ?? null,
                'bir_certificate_photo' => $validated['bir_certificate_photo'] ?? null,
            ]);

            logger('EDUCATIONS DATA:', $validated['educations']);
    
            if ($request->role === 'worker' && !empty($validated['educations'])) {
                foreach ($validated['educations'] as $edu) {
                    $user->educations()->create([
                        'level' => $edu['level'],
                        'school_name' => $edu['school_name'],
                        'course' => $edu['course'] ?? null,
                        'year_graduated' => $edu['year_graduated'] ?? null,
                    ]);
                }
            }

    
            // ✅ Save Certificates + certificate_photo
            if ($request->role === 'worker' && !empty($validated['certificates'])) {
                foreach ($validated['certificates'] as $index => $cert) {
                    $path = null;
                    // If photo exists for this certificate, save it separately
                    if ($request->hasFile("certificates.$index.certificate_photo")) {
                        $path = $request->file("certificates.$index.certificate_photo")
                            ->store('uploads/certificates', 'public');
                    }
    
                    $user->certificates()->create([
                        'title' => $cert['title'],
                        'issuing_organization' => $cert['issuing_organization'] ?? null,
                        'date_issued' => $cert['date_issued'] ?? null,
                        'certificate_photo' => $path,
                    ]);
                }
            }
    
            $token = $user->createToken('api_token')->plainTextToken;
    
            DB::commit();

            return response()->json([
                'message'      => 'User registered successfully',
                'user'         => $user->load('location'),
                'access_token' => $token,
            ], 201);

        } catch (Exception $e){
            DB::rollBack();

            return response()->json([
                "message" =>  $e->getMessage(),
            ], 500);
        }

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
            if($user->status === "pending"){
                throw ValidationException::withMessages([
                    'name' => ['We are reviewing your account. Please try again later.'],
                ]);
            } else {
                throw ValidationException::withMessages([
                    'name' => ['Inactive Account.'],
                ]);
            }
        }

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // public function me(Request $request)
    // {
    //     return response()->json($request->user());
    // }

    public function me(Request $request)
    {
        $user = $request->user()->load('location');

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }
}
