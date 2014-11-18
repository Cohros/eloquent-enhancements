<?php

class Phone extends AbstractModel
{
    protected $table = 'phones';

    protected $fillable = [
        'label',
        'number',
        'phone_type_id',
        'user_id',
    ];

    protected $validation_rules = [
        'label' => 'required',
        'number' => 'required|digits_between:4,20',
        'phone_type_id' => 'required|integer|exists:phones_types,id',
        'user_id' => 'required|integer|exists:users,id',
    ];

    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }

    public function type()
    {
        return $this->belongsTo('PhoneType', 'phone_type_id');
    }
}
