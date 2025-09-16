<?php

namespace App\Http\Controllers;

use App\Models\JobPost;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function jobsNearby(Request $request)
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50000', // in meters
        ]);

        $lat    = $request->lat;
        $lng    = $request->lng;
        $radius = $request->radius ?? 5000; // default 5km

        $jobs = JobPost::select(
                'job_posts.*',
                DB::raw("(6371000 * acos(cos(radians($lat)) 
                    * cos(radians(locations.lat)) 
                    * cos(radians(locations.lng) - radians($lng)) 
                    + sin(radians($lat)) 
                    * sin(radians(locations.lat)))) AS distance")
            )
            ->join('locations', 'locations.id', '=', 'job_posts.location_id')
            ->join('users', 'users.id', '=', 'job_posts.user_id')
            ->where('job_posts.status', 'open')
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->with('user', 'location:id,barangay,municipality,lat,lng')
            ->where('users.status', 'active')
            ->get();

        return response()->json($jobs);
    }

    public function workersNearby(Request $request)
    {
        $request->validate([
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
            'radius' => 'nullable|numeric|min:1|max:50000', // in meters
        ]);

        $lat    = $request->lat;
        $lng    = $request->lng;
        $radius = $request->radius ?? 5000;

        $workers = User::select(
                'users.*',
                'locations.barangay',
                'locations.municipality',
                DB::raw("(6371000 * acos(cos(radians($lat)) 
                    * cos(radians(locations.lat)) 
                    * cos(radians(locations.lng) - radians($lng)) 
                    + sin(radians($lat)) 
                    * sin(radians(locations.lat)))) AS distance")
            )
            ->join('locations', 'locations.id', '=', 'users.location_id')
            ->where('users.role', 'worker')
            ->where('users.status', 'active')
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->get();

        return response()->json($workers);
    }
}
