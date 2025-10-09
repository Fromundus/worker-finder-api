<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Booking;
use App\Models\Feedback;
use App\Models\JobPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $role = $request->query('role');
        $status = $request->query('status'); // "active" or "inactive"

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('contact_number', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        if ($status && $status !== 'all') {
            if ($status === 'active') {
                $query->where('status', 'active');
            } else if ($status === 'pending') {
                $query->where('status', operator: 'pending')->orWhereNull('status');
            } else if ($status === 'inactive') {
                $query->where('status', 'inactive');
            }
        }

        $users = $query->where("role", '!=', "admin")->orderBy('id', 'desc')->paginate($perPage);

        $roleCounts = [
            'total'      => User::count(),
            'worker'       => User::where('role', 'worker')->count(),
            'employer'       => User::where('role', 'employer')->count(),
        ];

        return response()->json([
            'users' => $users,
            'counts' => $roleCounts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:11', 'min:11'],
            'area' => ['required', 'string', 'max:255'],
            'notes' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'contact_number' => $request->contact_number,
            'area' => $request->area,
            'notes' => $request->notes,
            'password' => Hash::make(1234),
            'email' => $request->email,
            'email_verified_at' => Carbon::now(),
            'role' => $request->role,
        ]);

        return response()->noContent();
    }

    public function updateRole(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
            'role' => 'required|string',
        ]);

        DB::table('users')
            ->whereIn('id', $validated['ids'])
            ->update(['role' => $validated['role']]);

        $users = User::whereIn('id', $validated['ids'])->get();

        // foreach($users as $user){            
        //     ActivityLogger::log('update', 'account', "Updated account: #" . $user->id . " " . $user->name . " (changed role to " . $request->role . ")");
        // }

        return response()->json(['message' => 'Roles updated successfully']);
    }

    public function updateStatus(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string',
        ]);

        DB::table('users')
            ->whereIn('id', $validated['ids'])
            ->update(['status' => $validated['status']]);

        $users = User::whereIn('id', $validated['ids'])->get();

        return response()->json(['message' => 'Status updated successfully']);
    }

    public function changePassword(Request $request, $id){
        $user = User::where("id", $id)->first();

        if($user){
            $validator = Validator::make($request->all(), [
                "password" => "required|confirmed|string|min:6"
            ]);

            if($validator->fails()){
                return response()->json([
                    "status" => "422",
                    "message" => $validator->errors()
                ], 422);
            } else {
                $user->update([
                    "password" => Hash::make($request->password)
                ]);

                if($user){                    
                    return response()->json([
                        "status" => "200",
                        "message" => "Password Updated Successfully"
                    ], 200);
                } else {
                    return response()->json([
                        "status" => "500",
                        "message" => "Something Went Wrong"
                    ]);
                }
            }
        } else {
            return response()->json([
                "status" => "404",
                "message" => "User Not Found"
            ], 404);
        }
    }

    public function resetPasswordDefault(Request $request){
        $request->validate([
            'id' => 'required',
        ]);

        $user = User::findOrFail($request->id);

        $user->update([
            "password" => Hash::make(1234),
        ]);

        // ActivityLogger::log('reset', 'auth', "Reset the password for account: #" . $user->id . " " . $user->name);

        return response()->json(["message" => "Password Reset Success"], 200);
    }

    public function delete(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        $users = User::whereIn('id', $validated['ids'])->get();

        User::whereIn('id', $validated['ids'])->delete();

        // foreach($users as $user){
        //     ActivityLogger::log('delete', 'account', "Deleted account: #" . $user->id . " " . $user->name);
        // }

        return response()->json(['message' => 'Users deleted successfully']);
    }

    public function show(Request $request)
    {
        $user = $request->user();

        // Completed jobs (if worker → jobs they've done; if employer → jobs they've posted & filled)
        $completedApp = Application::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $completedBook = Booking::where('worker_id', $user->id)->where("status", "completed")->count();

        $completedJobs = $completedApp + $completedBook;

        $totalApplications = Application::where('user_id', $user->id)->count();

        // $feedback = Feedback::with('fromUser')
        //     ->where('to_user_id', $user->id)
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        $totalJobPosts = JobPost::where("user_id", $user->id)->count();

        $totalBookings = 0;

        if($user->role === "employer"){
            $totalBookings = Booking::where("employer_id", $user->id)->count();
        } else if ($user->role === "worker"){
            $totalBookings = Booking::where("worker_id", $user->id)->count();
        }

        $feedback = Feedback::with(['fromUser', 'toUser', 'jobPost', 'booking'])
        ->where('to_user_id', $user->id) // feedback received by logged-in user
        ->orderBy('created_at', 'desc')
        ->get();

        $averageRating= round($feedback->avg('rating'), 2);

        $ratingCounts = $feedback->groupBy('rating')->map->count();

        return response()->json([
            'user' => $user,
            'completedJobs' => $completedJobs,
            'totalApplications' => $totalApplications,
            'feedback' => $feedback,
            'averageRating' =>  $averageRating,
            'totalJobPosts' => $totalJobPosts,
            'totalBookings' => $totalBookings,
        ]);
    }

    public function showUserProfile(Request $request, $id)
    {
        $user = User::with('educations')->with('certificates')->findOrFail($id);

        // Completed jobs (if worker → jobs they've done; if employer → jobs they've posted & filled)
        $completedApp = Application::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $completedBook = Booking::where('worker_id', $user->id)->where("status", "completed")->count();

        $completedJobs = $completedApp + $completedBook;

        $totalApplications = Application::where('user_id', $user->id)->count();

        // $feedback = Feedback::with('fromUser')
        //     ->where('to_user_id', $user->id)
        //     ->orderBy('created_at', 'desc')
        //     ->get();

        $totalJobPosts = JobPost::where("user_id", $user->id)->count();

        $totalBookings = 0;

        if($user->role === "employer"){
            $totalBookings = Booking::where("employer_id", $user->id)->count();
        } else if ($user->role === "worker"){
            $totalBookings = Booking::where("worker_id", $user->id)->count();
        }

        $feedback = Feedback::with(['fromUser', 'toUser', 'jobPost', 'booking'])
        ->where('to_user_id', $user->id) // feedback received by logged-in user
        ->orderBy('created_at', 'desc')
        ->get();

        $averageRating= round($feedback->avg('rating'), 2);

        $ratingCounts = $feedback->groupBy('rating')->map->count();

        return response()->json([
            'user' => $user,
            'completedJobs' => $completedJobs,
            'totalApplications' => $totalApplications,
            'feedback' => $feedback,
            'averageRating' =>  $averageRating,
            'totalJobPosts' => $totalJobPosts,
            'totalBookings' => $totalBookings,
        ]);
    }

    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     $rules = [
    //         'name' => 'required|string|max:255',
    //         'contact_number' => 'nullable|string|max:20',
    //         'email' => 'nullable|email|unique:users,email,' . $user->id,
    //         'password' => 'nullable|min:6|confirmed',
    //         'address' => 'nullable|string',
    //         'lat' => 'nullable|string',
    //         'lng' => 'nullable|string',
    //     ];

    //     if ($user->role === 'worker') {
    //         $rules['skills'] = 'nullable|string';
    //         $rules['experience'] = 'nullable|string';
    //     }

    //     if ($user->role === 'employer') {
    //         $rules['business_name'] = 'nullable|string|max:255';
    //     }

    //     $validated = $request->validate($rules);

    //     if ($request->filled('password')) {
    //         $validated['password'] = bcrypt($request->password);
    //     } else {
    //         unset($validated['password']);
    //     }

    //     $user->update($validated);

    //     return response()->json([
    //         'message' => 'Profile updated successfully',
    //         'user' => $user,
    //     ]);
    // }

    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     $rules = [
    //         'name'           => 'required|string|max:255',
    //         'contact_number' => 'nullable|string|max:20',
    //         'email'          => 'nullable|email|unique:users,email,' . $user->id,
    //         'password'       => 'nullable|min:6|confirmed',

    //         'location_id'    => 'nullable|exists:locations,id', // NEW
    //         'lat'            => 'nullable|string',
    //         'lng'            => 'nullable|string',
    //     ];

    //     if ($user->role === 'worker') {
    //         $rules['skills'] = 'nullable|string';
    //         $rules['experience'] = 'nullable|string';
    //     }

    //     if ($user->role === 'employer') {
    //         $rules['business_name'] = 'nullable|string|max:255';
    //     }

    //     $validated = $request->validate($rules);

    //     if ($request->filled('password')) {
    //         $validated['password'] = bcrypt($request->password);
    //     } else {
    //         unset($validated['password']);
    //     }

    //     $user->update($validated);

    //     return response()->json([
    //         'message' => 'Profile updated successfully',
    //         'user'    => $user->load('location'), // include relationship if needed
    //     ]);
    // }

    // public function update(Request $request)
    // {
    //     $user = $request->user();

    //     // ✅ Decode only educations (same as register)
    //     $decodedEducations = json_decode($request->input('educations'), true);
    //     $request->merge([
    //         'educations' => $decodedEducations,
    //     ]);

    //     $baseRules = [
    //         "first_name" => "required|string|max:100",
    //         "middle_name" => "nullable|string|max:100",
    //         "last_name" => "required|string|max:100",
    //         "suffix" => "nullable|string|max:20",
    //         "contact_number" => "nullable|string|min:11|max:11",
    //         "birth_day" => "nullable|string",
    //         "email" => "nullable|email|unique:users,email," . $user->id,
    //         "password" => "nullable|confirmed|min:6",
    //         "sex" => "nullable|string|max:255",
    //         "religion" => "nullable|string|max:255",
    //         "civil_status" => "nullable|string|max:255",
    //         "height" => "nullable|string|max:255",
    //         "location" => "nullable|string",
    //         "lat" => "nullable|string",
    //         "lng" => "nullable|string",

    //         "has_disability" => "nullable|boolean",
    //         "disabilities" => "nullable|string",
    //         "disability_specify" => "nullable|string|max:255",

    //         'barangay_clearance_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //         'valid_id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //         'selfie_with_id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //         'business_permit_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //         'bir_certificate_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //     ];

    //     $extraRules = [];

    //     if ($user->role === 'worker') {
    //         $extraRules = [
    //             "skills" => "nullable|string",
    //             "skill_specify" => "nullable|string",
    //             "experience" => "nullable|string",

    //             'educations' => 'nullable|array',
    //             'educations.*.level' => 'required_with:educations|string|max:255',
    //             'educations.*.school_name' => 'required_with:educations|string|max:255',
    //             'educations.*.course' => 'nullable|string|max:255',
    //             'educations.*.year_graduated' => 'nullable|digits:4|min:0|max:2025',

    //             'certificates' => 'nullable|array',
    //             'certificates.*.title' => 'required|string|max:255',
    //             'certificates.*.issuing_organization' => 'nullable|string|max:255',
    //             'certificates.*.date_issued' => 'nullable|date',
    //             'certificates.*.certificate_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    //         ];

    //         if ($request->has_disability == true) {
    //             $extraRules = array_merge($extraRules, [
    //                 "disabilities" => "required|string",
    //             ]);
    //         }
    //     } else if ($user->role === 'employer') {
    //         $extraRules = [
    //             "employer_type" => "nullable|string",
    //             "business_name" => "nullable|string|max:255",
    //         ];
    //     }

    //     $validated = $request->validate(array_merge($baseRules, $extraRules));

    //     try {
    //         DB::beginTransaction();

    //         // ✅ Handle password
    //         if (!empty($validated['password'])) {
    //             $validated['password'] = bcrypt($validated['password']);
    //         } else {
    //             unset($validated['password']);
    //         }

    //         // ✅ Handle image uploads — store only filename
    //         $uploadFolder = 'uploads/users';
    //         $fileFields = [
    //             'barangay_clearance_photo',
    //             'valid_id_photo',
    //             'selfie_with_id_photo',
    //             'business_permit_photo',
    //             'bir_certificate_photo',
    //         ];

    //         foreach ($fileFields as $field) {
    //             if ($request->hasFile($field)) {
    //                 $file = $request->file($field);
    //                 $filename = uniqid() . '.' . $file->getClientOriginalExtension();
    //                 $file->storeAs($uploadFolder, $filename, 'public');
    //                 $validated[$field] = $filename;
    //             }
    //         }

    //         // ✅ Handle location conversion (same as register)
    //         $locationId = $user->location_id;
    //         if (!empty($validated['location'])) {
    //             [$barangay, $municipality] = array_map('trim', explode(',', $validated['location']));
    //             $location = \App\Models\Location::where('barangay', 'like', $barangay)
    //                 ->where('municipality', 'like', $municipality)
    //                 ->first();

    //             if ($location) {
    //                 $locationId = $location->id;
    //             }
    //         }
    //         $validated['location_id'] = $locationId;

    //         // ✅ Update user
    //         $user->update($validated);

    //         // ✅ Sync educations
    //         if ($user->role === 'worker' && !empty($validated['educations'])) {
    //             $user->educations()->delete();
    //             foreach ($validated['educations'] as $edu) {
    //                 $user->educations()->create([
    //                     'level' => $edu['level'],
    //                     'school_name' => $edu['school_name'],
    //                     'course' => $edu['course'] ?? null,
    //                     'year_graduated' => $edu['year_graduated'] ?? null,
    //                 ]);
    //             }
    //         }

    //         // ✅ Sync certificates (exact same logic as register)
    //         if ($user->role === 'worker' && !empty($validated['certificates'])) {
    //             $user->certificates()->delete();
    //             foreach ($validated['certificates'] as $index => $cert) {
    //                 $filename = null;
    //                 if ($request->hasFile("certificates.$index.certificate_photo")) {
    //                     $file = $request->file("certificates.$index.certificate_photo");
    //                     $filename = uniqid() . '.' . $file->getClientOriginalExtension();
    //                     $file->storeAs($uploadFolder, $filename, 'public');
    //                 }

    //                 $user->certificates()->create([
    //                     'title' => $cert['title'],
    //                     'issuing_organization' => $cert['issuing_organization'] ?? null,
    //                     'date_issued' => $cert['date_issued'] ?? null,
    //                     'certificate_photo' => $filename,
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Profile updated successfully',
    //             'user'    => $user->load('location', 'educations', 'certificates'),
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             "message" => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function update(Request $request)
    {
        $user = $request->user();

        Log::info($request);

        // ✅ Decode only educations (same as register)
        $decodedEducations = json_decode($request->input('educations'), true);
        $request->merge([
            'educations' => $decodedEducations,
        ]);

        $baseRules = [
            "first_name" => "required|string|max:100",
            "middle_name" => "nullable|string|max:100",
            "last_name" => "required|string|max:100",
            "suffix" => "nullable|string|max:20",
            "contact_number" => "nullable|string|min:11|max:11",
            "birth_day" => "nullable|string",
            "email" => "nullable|email|unique:users,email," . $user->id,
            "password" => "nullable|confirmed|min:6",
            "sex" => "nullable|string|max:255",
            "religion" => "nullable|string|max:255",
            "civil_status" => "nullable|string|max:255",
            "height" => "nullable|string|max:255",
            "location" => "nullable|string",
            "lat" => "nullable|string",
            "lng" => "nullable|string",

            "has_disability" => "nullable|boolean",
            "disabilities" => "nullable|string",
            "disability_specify" => "nullable|string|max:255",

            'barangay_clearance_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'valid_id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'selfie_with_id_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'business_permit_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'bir_certificate_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
        ];

        $extraRules = [];

        if ($user->role === 'worker') {
            $extraRules = [
                "skills" => "nullable|string",
                "skill_specify" => "nullable|string",
                "experience" => "nullable|string",

                'educations' => 'nullable|array',
                'educations.*.level' => 'required_with:educations|string|max:255',
                'educations.*.school_name' => 'required_with:educations|string|max:255',
                'educations.*.course' => 'nullable|string|max:255',
                'educations.*.year_graduated' => 'nullable|digits:4|min:0|max:2025',

                'certificates' => 'nullable|array',
                'certificates.*.id' => 'nullable|integer|exists:certificates,id',
                'certificates.*.title' => 'required|string|max:255',
                'certificates.*.issuing_organization' => 'nullable|string|max:255',
                'certificates.*.date_issued' => 'nullable|date',
                'certificates.*.certificate_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            ];

            if ($request->has_disability == true) {
                $extraRules = array_merge($extraRules, [
                    "disabilities" => "required|string",
                ]);
            }
        } else if ($user->role === 'employer') {
            $extraRules = [
                "employer_type" => "nullable|string",
                "business_name" => "nullable|string|max:255",
            ];
        }

        $validated = $request->validate(array_merge($baseRules, $extraRules));

        try {
            DB::beginTransaction();

            // ✅ Handle password
            if (!empty($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            } else {
                unset($validated['password']);
            }

            // ✅ Handle image uploads — store only filename
            $uploadFolder = 'uploads/users';
            $fileFields = [
                'barangay_clearance_photo',
                'valid_id_photo',
                'selfie_with_id_photo',
                'business_permit_photo',
                'bir_certificate_photo',
            ];

            // foreach ($fileFields as $field) {
            //     if ($request->hasFile($field)) {
            //         $file = $request->file($field);
            //         $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            //         $file->storeAs($uploadFolder, $filename, 'public');
            //         $validated[$field] = $filename;
            //     }
            // }

            foreach ($fileFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($uploadFolder, $filename, 'public');
                    $validated[$field] = $filename;
                } else {
                    // 👇 preserve the old value
                    $validated[$field] = $user->{$field};
                }
            }

            // ✅ Handle location conversion (same as register)
            $locationId = $user->location_id;
            if (!empty($validated['location'])) {
                [$barangay, $municipality] = array_map('trim', explode(',', $validated['location']));
                $location = \App\Models\Location::where('barangay', 'like', $barangay)
                    ->where('municipality', 'like', $municipality)
                    ->first();

                if ($location) {
                    $locationId = $location->id;
                }
            }
            $validated['location_id'] = $locationId;

            // ✅ Update user
            $user->update($validated);

            // ✅ Sync educations — only replace if new data is sent
            if ($user->role === 'worker' && $request->has('educations') && !empty($validated['educations'])) {
                $user->educations()->delete();
                foreach ($validated['educations'] as $edu) {
                    $user->educations()->create([
                        'level' => $edu['level'],
                        'school_name' => $edu['school_name'],
                        'course' => $edu['course'] ?? null,
                        'year_graduated' => $edu['year_graduated'] ?? null,
                    ]);
                }
            }

            // ✅ Sync certificates — only replace if new data is sent
            // if ($user->role === 'worker' && $request->has('certificates') && !empty($validated['certificates'])) {
            //     $user->certificates()->delete();
            //     foreach ($validated['certificates'] as $index => $cert) {
            //         $filename = null;
            //         if ($request->hasFile("certificates.$index.certificate_photo")) {
            //             $file = $request->file("certificates.$index.certificate_photo");
            //             $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            //             $file->storeAs($uploadFolder, $filename, 'public');
            //         }

            //         $user->certificates()->create([
            //             'title' => $cert['title'],
            //             'issuing_organization' => $cert['issuing_organization'] ?? null,
            //             'date_issued' => $cert['date_issued'] ?? null,
            //             'certificate_photo' => $filename,
            //         ]);
            //     }
            // }

            if ($user->role === 'worker' && $request->has('certificates')) {
                $incomingCerts = $validated['certificates'] ?? [];

                // Keep track of IDs that remain
                $existingIds = [];

                foreach ($incomingCerts as $index => $cert) {
                    // 1️⃣ If certificate has an ID → update
                    if (!empty($cert['id'])) {
                        $existingCert = $user->certificates()->find($cert['id']);
                        if ($existingCert) {
                            $filename = $existingCert->certificate_photo;

                            // Handle new upload
                            if ($request->hasFile("certificates.$index.certificate_photo")) {
                                $file = $request->file("certificates.$index.certificate_photo");
                                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                                $file->storeAs($uploadFolder, $filename, 'public');
                            }

                            $existingCert->update([
                                'title' => $cert['title'],
                                'issuing_organization' => $cert['issuing_organization'] ?? null,
                                'date_issued' => $cert['date_issued'] ?? null,
                                'certificate_photo' => $filename,
                            ]);

                            $existingIds[] = $existingCert->id;
                        }
                    } 
                    // 2️⃣ If no ID → create new one
                    else {
                        $filename = null;

                        if ($request->hasFile("certificates.$index.certificate_photo")) {
                            $file = $request->file("certificates.$index.certificate_photo");
                            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                            $file->storeAs($uploadFolder, $filename, 'public');
                        }

                        $newCert = $user->certificates()->create([
                            'title' => $cert['title'],
                            'issuing_organization' => $cert['issuing_organization'] ?? null,
                            'date_issued' => $cert['date_issued'] ?? null,
                            'certificate_photo' => $filename,
                        ]);

                        $existingIds[] = $newCert->id;
                    }
                }

                // 3️⃣ Delete certificates that were removed on frontend
                $user->certificates()->whereNotIn('id', $existingIds)->delete();
            }


            DB::commit();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user'    => $user->load('location', 'educations', 'certificates'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => $e->getMessage(),
            ], 500);
        }
    }
}
