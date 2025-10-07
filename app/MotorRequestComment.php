<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MotorRequestComment extends Model
{
    protected $table = 'motor_request_comments';
    protected $fillable = [
        'user_id',
        'user_role',
        'user_name' , 

        'comment' , 
        'comment_file' ,
        'comment_date' ,

        'reciver_id',
        'reciver_role',
        'reciver_name',

        'request_id',
        'request_status',
        ];   

    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

    public function reciver()   
    {
        return $this->belongsTo(User::class , 'reciver_id');
    }

    public function request()
    {
        return $this->belongsTo(MotorRequest::class , 'request_id');
    }

}
