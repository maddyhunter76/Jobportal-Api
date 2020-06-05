<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Jobseeker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobseekerController extends Controller

{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $jobseeker = Auth::user();
        $data['jobs'] = $jobseeker->jobs()->where('status', 1)->get();
        $data['profile'] = $jobseeker->profile;
        return response()->json(['resp' => 1, 'result' => $data]);
    }

    public function show(Request $request)
    {
        return response()->json(['resp' => 1, 'user' =>  Auth::user()]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'profile' => 'max:2000|mimes:jpg,png,jpeg,JPEG,JPG,PNG',
            'cv' => 'max:2000|mimes:pdf',
        ]);

        $jobseeker = Auth::user();
        $attributes = $request->all();

        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $filename = md5(microtime()) . '.' . $file->getClientOriginalExtension();
            $path = base_path() . '/public/jobseeker/images';

            if (!file_exists($path)) {
                mkdir($path, 0777);
            }

            $file->move($path, $filename);
            $attributes['profile'] = $filename;
        }

        if ($request->hasFile('cv')) {
            $file = $request->file('cv');
            $filename = md5(microtime()) . '.' . $file->getClientOriginalExtension();
            $path = base_path() . '/public/jobseeker/cv';

            $file->move($path, $filename);
            $attributes['cv'] = $filename;
        }

        if ($jobseeker->update($attributes)) {
            return response()->json(['resp' => 1, 'user' => $jobseeker]);
        }
        return response()->json(['resp' => 0]);
    }

    public function downloadCV(Request $request)
    {
        try {
            //check if email is present
            if(!$request->has('email')) throw new \Exception('No Email');

            $jobseeker = Jobseeker::where('email', $request->email)->first();
            //check if user exist
            if (empty($jobseeker) ) throw new \Exception('User Not Found');

            //check if user has cv
            if (empty($jobseeker->cv))  throw new \Exception('User Not Found');

             //check if file exist and is file
            $file = base_path() . '/public/jobseeker/cv/' . $jobseeker->rawcv;
            if(!file_exists($file) || ! is_file($file)){
                throw new \Exception('File Not Found');
            }

            //download file 
            return response()->download($file, $jobseeker->email . ".pdf", ['content-type' => "application/pdf"]);

        } catch (\Exception $e) {
            return response()->json(["resp" => 0, "message" => $e->getMessage()]);
        }
    }
}
