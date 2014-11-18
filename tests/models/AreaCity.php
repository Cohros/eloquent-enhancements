<?php

class AreaCity extends AbstractModel
{
    protected $table = 'areas_cities';

    protected $fillable = [
        'area_id',
        'city_id',
    ];

    protected $validation_rules = [
        'area_id' => 'required|integer|exists:areas,id',
        'city_id' => 'required|integer|exists:cities,id',
    ];

    public function area()
    {
        return $this->belongsTo('Area', 'area_id');
    }

    public function city()
    {
        return $this->belongsTo('City', 'city_id');
    }
}
