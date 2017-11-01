<?php

class AreaCity extends AbstractModel
{
    protected $table = 'areas_cities';

    protected $primaryKey = 'id_area_city';

    protected $fillable = [
        'id_area',
        'id_city',
    ];

    protected $validation_rules = [
        'id_area' => 'required|integer|exists:areas,id_area',
        'id_city' => 'required|integer|exists:cities,id_city',
    ];

    public function area()
    {
        return $this->belongsTo('Area', 'id_area');
    }

    public function city()
    {
        return $this->belongsTo('City', 'id_city');
    }
}
