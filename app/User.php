<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    const ROLE = array(
        "TUTOR" => "tutor",
        "STUDENT" => "student"
    );

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'birth_date', 'role', 'photo', 'contact', 'company_id', 'address', 'balance', 'firebase_token', 'jenjang'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function detail()
    {
        return $this->hasOne('App\TutorDetail');
    }
    public function tutorSubject()
    {
        return $this->hasMany('App\TutorSubject');
    }
    public function rating()
    {
        return $this->hasMany('App\Rating','tutor_id','id');
    }
    public function avrating()
    {
        return $this->hasMany('App\Rating','tutor_id','id');
    }
    public function tutorDoc()
    {
        return $this->hasMany('App\TutorDoc','tutor_id','id');
    }

    public function room_vc()
    {
        return $this->hasMany('App\RoomVC','tutor_id');
    }

    public function room_chat()
    {
        return $this->hasMany('App\RoomChat','tutor_id');

    }

    public function history_vc()
    {
        return $this->hasMany('App\HistoryVC','tutor_id');

    }
}
