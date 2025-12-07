<?php 

namespace App\Repositories;

use App\Models\User;
use App\Models\UserContact;
use App\Models\UserReporting;

class UserRepository
{
    public function createUser($data)
    {
        return User::create($data);
    }

    public function createUserContact($data)
    {
        return UserContact::create($data);
    }

    public function createUserReporting($data)
    {
        return UserReporting::create($data);
    }


    public function findUserById($id)
    {
        return User::find($id);
    }

    public function findUserByUuId($uuid)
    {
        return User::where('uuid',$uuid)->first();
    }

     
}
