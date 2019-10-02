<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
//Importing laravel-permission models
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Response;
use Session;
class RoleController extends Controller{
    public function __construct() {
        $this->middleware(['auth', 'isAdmin']);//isAdmin middleware lets only users with a //specific permission permission to access these resources
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $roles = Role::all();//Get all roles
        return view('admin.roles.index')->with('roles', $roles);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $permissions = Permission::all();//Get all permissions
        return view('admin.roles.create', ['permissions'=>$permissions]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //Validate name and permissions field
        $this->validate($request, [
            'name'=>'required|unique:roles|max:10',
            'permissions' =>'required',
        ]);
        $name = $request['name'];
        $role = new Role();
        $role->name = $name;
        $permissions = $request['permissions'];
        if($role->save()):
            //Looping thru selected permissions
            foreach ($permissions as $permission) {
                $p = Permission::where('id', '=', $permission)->firstOrFail(); 
                //Fetch the newly created role and assign permission
                $role = Role::where('name', '=', $name)->first(); 
                $role->givePermissionTo($p);
            }
            $response = ['status'=>100,'message'=>'Role created','url'=>route('roles.index')];
        else:
            $response = ['status'=>102,'message'=>'Something went wrong with saving data'];
        endif;
        return Response::json($response);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        return redirect('roles');
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        return view('admin.roles.edit', compact('role', 'permissions'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $role = Role::findOrFail($id);//Get role with the given id
        //Validate name and permission fields
        $this->validate($request, [
            'name'=>'required|max:10|unique:roles,name,'.$id,
            'permissions' =>'required',
        ]);
        $input = $request->except(['permissions']);
        $permissions = $request['permissions'];
        if($role->fill($input)->save()):
            $p_all = Permission::all();//Get all permissions
            foreach ($p_all as $p) {
                $role->revokePermissionTo($p); //Remove all permissions associated with role
            }
            foreach ($permissions as $permission) {
                $p = Permission::where('id', '=', $permission)->firstOrFail(); //Get corresponding form //permission in db
                $role->givePermissionTo($p);  //Assign permission to role
            }
            $response = ['status'=>100,'message'=>'Role updated','url'=>route('roles.index')];
        else:
            $response = ['status'=>102,'message'=>'Something went wrong with saving data'];
        endif;
        return Response::json($response);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        $role = Role::findOrFail($id);
        if($role->delete()):
            $response = ['status'=>100,'message'=>'Role deleted','url'=>route('roles.index')];
        else:
            $response = ['status'=>102,'message'=>'Something went wrong with deleting data'];
        endif;
        return Response::json($response);
    }
}