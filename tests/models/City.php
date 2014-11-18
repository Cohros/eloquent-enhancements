<?php

class City extends AbstractModel
{
    protected $table = 'cities';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required'
    ];

    public function regions()
    {
        return $this->belongsToMany('Region', 'regions_cities', 'city_id', 'region_id');
    }
}
