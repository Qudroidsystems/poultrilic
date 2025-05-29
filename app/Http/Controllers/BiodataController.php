<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BioModel;
use Illuminate\Http\Request;
use File;
use App\Traits\ImageManager as TraitsImageManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;


class BiodataController extends Controller
{
    use TraitsImageManager;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        echo "yes";
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', ]);
        $path = storage_path('images/staffavatar');
        !is_dir($path) && mkdir($path, 0775, true);


         if( $file = $request->file('avatar')) {
            $filename = $request->fname.'_'. $request->lname;

                    if (User::where('id',$request->id)->whereNotNull('avatar')->get()) {

                        $filename = User::where('id',$request->id)->get(['avatar']);
                        foreach ($filename as $key => $value) {
                          $datafile = $value->avatar;
                        }


                        if( Storage::disk('public')->exists('images/staffavatar/'.$datafile)){
                           Storage::disk('public')->delete('images/staffavatar/'.$datafile);
                           $this->uploads($file,$path,$request->id);
                        }else{
                            $this->uploads($file,$path,$request->id);
                            // dd('File does not exist.');
                        }
                    }else {

                        echo "something went wrong";
                            }

                //updateOrCreate
               BioModel::updateOrCreate(
                ['user_id' =>  $request->id],
                ['firstname' => $request->fname,
                'lastname' => $request->lname,
                'othernames' => $request->oname,
                'address' => $request->address,
                'gender' => $request->gender,
                'phone' =>$request->phone,
                'maritalstatus' => $request->maritalstatus,
                'nationality' => $request->nationality,
                'dob' => $request->dob]
            );
            return back() ->with('success','You have successfully updated data.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {

        $user = User::find($id);
        $userroles = $user->roles->all();
        $userbio = $user->bio;
        return view('users.settings',
                   compact('user'),
               compact('userroles'))
                    ->with("userbio",$userbio);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }


    public function ajaxemailupdate(Request $request)
    {

        //$data =$request->id;
        $data = User::updateOrCreate(
            ['id' =>  $request->userid],
            ['email' => $request->emailaddress]);

        if ($data) {
            $success = true;
            $message = "Email changed successfuly to ".$request->emailaddress;
        } else {
            $success = true;
            $message = "attempt not successful";
        }

        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);

    }


    public function ajaxpasswordupdate(Request $request)
    {

        $data = User::updateOrCreate(
            ['id' =>  $request->userid],
            ['password' => Hash::make($request->password),
            'wpassword' => $request->password]);

        if ($data) {
            $success = true;
            $message = "Password Reset Successful";
        } else {
            $success = true;
            $message = "attempt not successful";
        }

        //  return response
        return response()->json([
            'success' => $success,
            'message' => $message,
        ]);

    }


    public function update(Request $request, string $id)
    {

           echo "yyyyyy";

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
