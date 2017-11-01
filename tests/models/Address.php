<?php

class Address extends AbstractModel
{
    protected $table = 'addresses';

    protected $primaryKey = 'id_address';

    protected $fillable = [
        'address',
        'postal_code',
        'addressable_id',
        'addressable_type',
    ];

    protected $validation_rules = [
        'address' => 'required',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
