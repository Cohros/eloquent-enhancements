<?php

class Car extends AbstractModel
{
    protected $table = 'cars';

    protected $fillable = [
        'vendor',
        'model',
        'user_id',
    ];

    protected $validation_rules = [
    ];

    public function user()
    {
        return $this->belongsTo('User', 'user_id');
    }
}
