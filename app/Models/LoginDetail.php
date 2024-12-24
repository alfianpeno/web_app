<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LoginDetail extends Model
{
    protected $fillable = [
        'user_id', 'ip', 'date', 'details', 'type', 'created_by'   
    ];

    public static function Getuser($type = 'user',$user_id = null)
    {
        if($user_id != null && $type == 'user')
        {
            $user = User::where('id',$user_id)->first();
          
        }
        elseif($user_id != null &&  $type == 'customer')
        {
            $user = Customer::where('id',$user_id)->first();     
        }
        elseif($user_id != null &&  $type == 'vender')
        {
            $user = Vender::where('id',$user_id)->first();     
        }

        if($user)
        {
            return $user;
        }
        else
        {
            return [];
        }
    }
}
