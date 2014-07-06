<?php

class PhoneType extends BaseModel
{
    protected $table = 'phones_types';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required',
    ];

    public function phones()
    {
        return $this->hasMany('Phone', 'phone_type_id');
    }
}
