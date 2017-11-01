<?php

class Phone extends AbstractModel
{
    protected $table = 'phones';

    protected $primaryKey = 'id_phone';

    protected $fillable = [
        'label',
        'number',
        'id_phone_type',
        'id_user',
    ];

    protected $validation_rules = [
        'label' => 'required',
        'number' => 'required|digits_between:4,20',
        'id_phone_type' => 'required|integer|exists:phones_types,id_phone_type',
        'id_user' => 'required|integer|exists:users,id_user',
    ];

    public function user()
    {
        return $this->belongsTo('User', 'id_user');
    }

    public function type()
    {
        return $this->belongsTo('PhoneType', 'id_phone_type');
    }
}
