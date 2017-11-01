<?php

class User extends AbstractModel
{
    protected $table = 'users';

    protected $primaryKey = 'id_user';

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
        return $this->hasMany('Phone', 'id_user');
    }
    
    public function addresses()
    {
        return $this->morphMany('Address', 'addressable');
    }

    public function cars()
    {
        return $this->hasMany('Car', 'id_user');
    }
}
