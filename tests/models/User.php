<?php

class User extends AbstractModel
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];

    protected $validation_rules = [
        'name' => 'required',
        'email' => 'email',
    ];

    protected $relationshipsLimits = [
        'phones' => '1:2',
    ];

    public function phones()
    {
        return $this->hasMany('Phone', 'user_id');
    }
    
    public function addresses()
    {
        return $this->morphMany('Address', 'addressable');
    }

    public function cars()
    {
        return $this->hasMany('Car', 'user_id');
    }
}
