<?php

class Region extends BaseModel
{
    protected $table = 'regions';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required'
    ];

    public function cities()
    {
        return $this->belongsToMany('City', 'regions_cities', 'region_id', 'city_id');
    }
}
