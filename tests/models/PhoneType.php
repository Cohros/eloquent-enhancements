<?php

class PhoneType extends AbstractModel
{
    protected $table = 'phones_types';

    protected $primaryKey = 'id_phone_type';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required',
    ];

    public function phones()
    {
        return $this->hasMany('Phone', 'id_phone_type');
    }
}
