<?php

class User extends BaseModel
{
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
    ];

    protected $validation_rules = [
        'name' => 'required',
        'email' => 'email|unique:users,email',
    ];

    public function phones()
    {
        return $this->hasMany('Phone', 'user_id');
    }
}
