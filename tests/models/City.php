<?php

class City extends AbstractModel
{
    protected $table = 'cities';

    protected $primaryKey = 'id_city';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required'
    ];

    public function regions()
    {
        return $this->belongsToMany('Region', 'regions_cities', 'id_city', 'id_region');
    }
}
