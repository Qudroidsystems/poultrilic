<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BioModel;
use App\Models\Student;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{


    function __construct()
    {
         $this->middleware('permission:View user|Create user|Update user|Delete user', ['only' => ['index','store']]);
         $this->middleware('permission:Create user', ['only' => ['create','store']]);
         $this->middleware('permission:Update user', ['only' => ['edit','update']]);
         $this->middleware('permission:Delete user', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): View
    {
        // Page title
        $pagetitle = "User Management";

        // Fetch paginated users (10 per page to match JavaScript)
        $data = User::latest()->paginate(10);
        $roles = Role::pluck('name', 'name')->toArray();
        $role_permissions = Role::all(); // Simplified, as permissions aren't used in view

        // Calculate users per role for chart
        $role_counts = [];
        foreach ($roles as $role) {
            $role_counts[$role] = User::role($role)->count();
        }
        $role_counts['No Role'] = User::doesntHave('roles')->count();

        // Debug logs (only in debug mode)
        if (config('app.debug')) {
            \Log::info('Roles for select:', $roles);
            \Log::info('User roles example:', User::first()->getRoleNames()->toArray());
        }

        // Note: Removed $students as it's unused in the view
        return view('users.index', compact('data', 'roles', 'role_permissions', 'pagetitle', 'role_counts'))
            ->with('i', ($request->input('page', 1) - 1) * 10);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::pluck('name')->all();
        return response()->json(['roles' => $roles]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): View
    {

          //page title
          $title = "Create User";


        $roles = Role::pluck('name','name')->all();
        return view('users.create',compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

     public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->hasPermissionTo('Create user')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create users',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'roles'    => 'required|array|min:1',
                'roles.*'  => 'exists:roles,name',
            ]);

            // Split full name into first_name and last_name
            $nameParts = explode(' ', trim($validated['name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName  = isset($nameParts[1]) ? $nameParts[1] : '';

            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'username'   => $validated['email'], // Set username = email on create
                'email'      => $validated['email'],
                'password'   => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['roles']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("User creation failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user',
            ], 500);
        }
    }



     public function show($id): View
    {
        $pagetitle = "User Management";
        $user = User::with('roles')->findOrFail($id);

        return view('users.show', compact('user','pagetitle'));
    }

        public function overview($id): View
    {
        $pagetitle = "User Management";
        $user = User::with('roles')->findOrFail($id);

        return view('users.overview', compact('user','pagetitle'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id): View
    {
        $user = User::find($id);
        $roles = Role::pluck('name','name')->all();
        $userRole = $user->roles->pluck('name','name')->all();

        return view('users.edit',compact('user','roles','userRole'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

 public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Validation rules
            $validated = $request->validate([
                'first_name'       => 'required|string|max:255',
                'last_name'        => 'nullable|string|max:255',
                'email'            => 'required|email|unique:users,email,' . $id,
                'phone_number'     => 'nullable|string|max:20',
                'gender'           => 'nullable|in:male,female,other',
                'date_of_birth'    => 'nullable|date|before:today',
                'password'         => 'nullable|confirmed|min:8',
                'profile_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB max
            ]);

            // Prepare input data
            $input = [
                'first_name'   => $validated['first_name'],
                'last_name'    => $validated['last_name'] ?? '',
                'email'        => $validated['email'],
                'username'     => $validated['email'], // Keep username = email
                'phone_number' => $validated['phone_number'] ?? null,
                'gender'       => $validated['gender'] ?? null,
                'date_of_birth'=> $validated['date_of_birth'] ?? null,
            ];

            // Handle password update
            if ($request->filled('password')) {
                $input['password'] = Hash::make($validated['password']);
            }

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if exists
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                // Store new image
                $path = $request->file('profile_image')->store('profile_images', 'public');
                $input['profile_image'] = $path;
            }

            // Update user
            $user->update($input);

            // Note: Roles are managed separately in your admin user list
            // If you want to allow role changes here too, uncomment below:
            // if ($request->has('roles')) {
            //     $user->syncRoles($request->input('roles'));
            // }

            // Reload roles for response
            $user->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id'             => $user->id,
                    'name'           => $user->name,
                    'first_name'     => $user->first_name,
                    'last_name'      => $user->last_name,
                    'email'          => $user->email,
                    'phone_number'   => $user->phone_number,
                    'gender'         => $user->gender,
                    'date_of_birth'  => $user->date_of_birth?->format('Y-m-d'),
                    'profile_image'  => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                    'roles'          => $user->roles->pluck('name')->toArray(),
                    'updated_at'     => $user->updated_at->format('d M Y, H:i'),
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error("User profile update failed (ID: $id): " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ], 500);
        }
    }


    /**
     * Show the form for creating a new user from student.
     *
     * @return \Illuminate\Http\Response
     */
    public function createFromStudentForm(): View
    {
        $roles = Role::pluck('name','name')->all();
        $students = Student::select('id', 'admissionNo', 'firstname', 'lastname')
                        ->where('statusId', 1) // Assuming 1 is for active students
                        ->orderBy('admissionNo')
                        ->get();

        return view('users.add-student-user', compact('roles', 'students'));
    }

    /**
     * Store a newly created user from student in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFromStudent(Request $request): RedirectResponse
    {
        $this->validate($request, [
            'student_id' => 'required|exists:studentregistration,id',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        // Get student data
        $student = Student::findOrFail($request->student_id);

        // Create user
        $user = new User();
        $user->name = $student->firstname . ' ' . $student->lastname;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->student_id = $student->id; // Link to student record
        $user->save();

        // Assign role
        $user->assignRole($request->input('roles'));

        // Also create or update the BioModel entry if necessary
        BioModel::updateOrCreate(
            ['user_id' => $user->id],
            [
                'firstname' => $student->firstname,
                'lastname' => $student->lastname,
                'othernames' => $student->othername ?? '',
                'phone' => '', // You could add these fields to your form if needed
                'address' => $student->home_address ?? '',
                'gender' => $student->gender ?? '',
                'maritalstatus' => '',
                'nationality' => $student->nationlity ?? '',
                'dob' => $student->dateofbirth ?? ''
            ]
        );

        return redirect()->route('users.index')
                        ->with('success', 'Student added as user successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    public function destroy($id)
    {
        Log::debug("Attempting to delete user ID: {$id}");
        try {
            $user = User::findOrFail($id);

            // Delete related BioModel
            Log::debug("Deleting BioModel for user ID: {$id}");
            BioModel::where('user_id', $id)->delete();

            // Remove roles (Spatie)
            Log::debug("Removing roles for user ID: {$id}");
            $user->roles()->detach();

            // Delete the user
            Log::debug("Deleting user ID: {$id}");
            $user->delete();

            Log::debug("User ID: {$id} deleted successfully");
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Delete user error for ID {$id}: {$e->getMessage()}\nStack trace: {$e->getTraceAsString()}");
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
            ], 500);
        }
    }
}
