<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\LoginDetail;
use App\Models\User;
use App\Models\Vender;




class UsersLogController extends Controller
{
    // public function index()
    // {

    //     $logindetails = LoginDetail::where('created_by', '=', \Auth::user()->creatorId())->get();
    //     // $logindetails = \DB::table('login_details')
    //     // ->join('users', 'login_details.user_id', '=', 'users.id')
    //     // ->join('customers','login_details.user_id', '=', 'customers.id')
    //     // ->join('venders','login_details.user_id', '=', 'venders.id')
    //     // ->select(\DB::raw('login_details.*, users.name as user_name , users.email as user_email, customers.name as customer_name, customers.email as customer_email, venders.name as vender_name, venders.email as vender_email'))
    //     // ->where(['login_details.created_by' => \Auth::user()->creatorId()])
    //     // ->get();
    //     // dd($logindetails);   
    //     return view('userlogs.index', compact('logindetails'));


    // }


    public function  index(Request $request)
    {
        $logindetails = LoginDetail::where('created_by', '=', \Auth::user()->creatorId());

        $usersList1 = User::where('created_by', '=', \Auth::user()->creatorId())->whereNotIn('type', ['super admin', 'company'])->pluck('name', 'name');
        $usersList2 = Customer::where('created_by', \Auth::user()->creatorId())->pluck('name', 'name');
        $usersList3 = Vender::where('created_by', \Auth::user()->creatorId())->pluck('name', 'name');

        $merged = $usersList1->merge($usersList2);
        $usersList = $merged->merge($usersList3);
        // dd($merged1);
        // dd($usersList);


        $usersList->prepend('All','');  
        if (isset($request->month) && !empty($request->month)) {
            $time = strtotime($request->month);
            $month = date("m", $time);

            $logindetails = $logindetails->whereMonth('date', $month);
        }

        if (isset($request->user) && !empty($request->user)) 
        {
            $user = User::where('name',$request->user)->first();
            if(empty($user)){

                $user=Customer::where('name',$request->user)->first();
            }
            if(empty($user)){
                $user=Vender::where('name',$request->user)->first();
            }

            $logindetails = $logindetails->where('user_id', $user->id);
        }
        $logindetails = $logindetails->get();


        return view('userlogs.index', compact('logindetails', 'usersList'));
    }



    public function show($id)
    {
        $details = LoginDetail::find($id);

        return view('userlogs.view', compact('details'));
    }

    public function destroy($id)
    {
        LoginDetail::where('id', $id)->delete();

        return redirect()->route('userlogs.index')->with(
            'success',
            'Userlogs successfully deleted.'
        );
    }
}
