<?php

class Car extends AbstractModel
{
    protected $table = 'cars';

    protected $primaryKey = 'id_car';

    protected $fillable = [
        'vendor',
        'model',
        'id_user',
    ];

    protected $validation_rules = [
    ];

    public function user()
    {
        return $this->belongsTo('User', 'id_user');
    }
}
