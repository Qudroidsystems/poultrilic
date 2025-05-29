<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BadgeModel;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware('permission:View role|Create role|Update role|Delete role|Add user-role|Update user-role|Remove user-role', ['only' => ['index','store']]);
         $this->middleware('permission:Create role', ['only' => ['create','store']]);
         $this->middleware('permission:Update role', ['only' => ['edit','update']]);
         $this->middleware('permission:Delete role', ['only' => ['destroy','removeuserRole']]);
         $this->middleware('permission:Update user-role', ['only' => ['adduser','updateuserrole']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request):View
    {

        #page title
        $pagetitle = "Role Management";

        $roles = Role::orderBy('name','DESC')->get();
        $role = Role::orderBy('name','DESC')->get();


        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title ){
               $array[] = $title->title ;
         }

        $ar = implode(',', $array);
        $ex = explode(',',$ar);
       return view('roles.index', compact('role'), compact('roles'),compact('permission'))->with('perm_title',$ex)->with('pagetitle',$pagetitle);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): View
    {
        $permission = Permission::get();
        return view('roles.create',compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): RedirectResponse
    {
        #page title
        $pagetitle = "Role Management";
    
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required|array', // Ensure permission is an array
            'permission.*' => 'exists:permissions,id', // Validate each permission ID exists
            'title' => 'nullable|string', // Optional validation for title
            'badge' => 'nullable|string', // Optional validation for badge
        ]);
    
        // Create the role
        $role = Role::create([
            'name' => $request->input('name'),
            'title' => $request->input('title'),
            'badge' => $request->input('badge'),
        ]);
    
        // Find permissions by their IDs
        $permissionIds = $request->input('permission');
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
    
        // Sync permissions to the role
        $role->syncPermissions($permissions);
    
        return redirect()->route('roles.index')
                        ->with('success', 'Role created successfully')
                        ->with('pagetitle', $pagetitle);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pagetitle = "Role Management";
    
        // Count users with the role
        $userRoleCount = DB::table('model_has_roles')->where('role_id', $id)->count();
    
        // Fetch paginated users with the specified role
        $usersWithRole = User::leftJoin("roles", "roles.id", "=", "users.id")
            ->join("model_has_roles", "model_has_roles.model_id", "=", "users.id")
            ->where("model_has_roles.role_id", $id)
            ->select([
                'users.id as id',
                'users.name as username',
                'users.email as email',
                'users.created_at as created_at',
                'model_has_roles.role_id as roleid'
            ])
            ->paginate(5); // Set per-page limit to match frontend (perPage = 5)
    
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();
        $rolePermissions2 = DB::table("role_has_permissions")
            ->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
    
        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title) {
            $array[] = $title->title;
        }
    
        $ar = implode(',', $array);
        $ex = explode(',', $ar);
    
        Session::put('role_url', request()->fullUrl());
    
        return view('roles.show', compact(
            'role',
            'rolePermissions',
            'rolePermissions2',
            'usersWithRole',
            'userRoleCount',
            'pagetitle'
        ))->with('perm_title', $ex);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id): View
    {

         #page title
         $pagetitle = "Role Management";


        $role = Role::find($id);
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
         ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
        $permission = Permission::get();
        $perm_title = Permission::get(['title']);
        $array = [];
        foreach ($perm_title as $title ){
               $array[] = $title->title ;
         }

        $ar = implode(',', $array);
        $ex = explode(',',$ar);



        return view('roles.edit',compact('role','permission','rolePermissions'))->with('perm_title',$ex)->with('pagetitle',$pagetitle);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id): RedirectResponse
    {
        #page title
        $pagetitle = "Role Management";
    
        $this->validate($request, [
            'name' => 'required|unique:roles,name,' . $id, // Ensure name is unique except for current role
            'permission' => 'required|array', // Ensure permission is an array
            'permission.*' => 'exists:permissions,id', // Validate each permission ID exists
            'badge' => 'nullable|string', // Optional validation for badge
        ]);
    
        $role = Role::findOrFail($id); // Use findOrFail for safety
        $role->update([
            'name' => $request->input('name'),
            'badge' => $request->input('badge'),
        ]);
    
        // Convert permission IDs to permission names
        $permissionIds = $request->input('permission');
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();
    
        // Sync permissions to the role
        $role->syncPermissions($permissions);
    
        if (session('role_url')) {
            return redirect(session('role_url'))
                ->with('success', 'Role Updated successfully')
                ->with('pagetitle', $pagetitle);
        }
    
        return redirect()->route('roles.index')
            ->with('success', 'Role Updated successfully')
            ->with('pagetitle', $pagetitle);
    }

      /**
     * add user to role..
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adduser($id): View
    {

         #page title
         $pagetitle = "Role Management";

        $role = Role::find($id);
        $r = $role->name;
        $users = User::whereDoesntHave('roles', function ($q) use ($r)  {
                            $q->where('name', $r); })->get();
        return view('roles.adduser')->with('role',$role)
                                    ->with('users',$users)->with('pagetitle',$pagetitle);
    }



      /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateuserrole(Request $request): RedirectResponse
    {
        #page title
        $pagetitle = "Role Management";
    
        $this->validate($request, [
            'users' => 'required|array', // Validate users as an array
            'users.*' => 'exists:users,id', // Ensure each user ID exists
            'roleid' => 'required|exists:roles,id', // Validate role ID
        ]);
    
        $role = Role::findOrFail($request->input('roleid')); // Find role by ID
        $userIds = $request->input('users'); // Get array of user IDs
    
        foreach ($userIds as $userId) {
            $user = User::findOrFail($userId); // Find user or fail
            $user->assignRole($role->name); // Assign role to each user
        }
    
        return redirect()->route('roles.show', $role->id)
                        ->with('success', 'Users added to role successfully')
                        ->with('pagetitle', $pagetitle);
    }

  /**
     * Remove user from role.
     */
    public function removeuserrole(Request $request, $userid, $roleid): JsonResponse
{
    \Log::info("Removing user role", ['user_id' => $userid, 'role_id' => $roleid]);
    try {
        $user = User::findOrFail($userid);
       // \Log::info("User found", ['user_id' => $userid]);
        $role = Role::findOrFail($roleid);
       // \Log::info("Role found", ['role_id' => $roleid]);
        $user->removeRole($role->name);
      //  \Log::info("User role removed successfully", ['user_id' => $userid, 'role_id' => $roleid]);
        return response()->json(['success' => true, 'message' => 'User role removed successfully']);
    } catch (\Exception $e) {
        \Log::error("Error removing user role", [
            'error' => $e->getMessage(),
            'user_id' => $userid,
            'role_id' => $roleid,
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['message' => 'Error removing user role: ' . $e->getMessage()], 500);
    }
}

    // delete user
    public function delete($id)
    {
        $delete = User::destroy($id);

        // check data deleted or not
        if ($delete == 1) {
            $success = true;
            $message = "User deleted successfully";
        } else {
            $success = true;
            $message = "User not found";
        }

        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id): RedirectResponse
    {
         #page title
         $pagetitle = "Role Management";

        DB::table("roles")->where('id',$id)->delete();
        return redirect()->route('roles.index')
                        ->with('success','Role deleted successfully')->with('pagetitle',$pagetitle);
    }


}
