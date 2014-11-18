<?php

class Area extends AbstractModel
{
    protected $table = 'areas';

    protected $fillable = [
        'name',
    ];

    protected $validation_rules = [
        'name' => 'required'
    ];

    protected $relationshipsModels = [
        'cities' => 'AreaCity',
    ];

    public function cities()
    {
        return $this->belongsToMany('City', 'areas_cities', 'area_id', 'city_id')->withPivot('id');
    }
}
