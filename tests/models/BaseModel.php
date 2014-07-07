<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Validator as Validator;

class BaseModel extends Eloquent
{
    use Sigep\EloquentEnhancements\Traits\Error;
    use Sigep\EloquentEnhancements\Traits\SaveAll;

    public function save(array $options = [])
    {
        $data = ($options) ? $options : $this->getAttributes();
        $validator = Validator::make($data, $this->validation_rules);
        if ($validator->fails()) {
            $this->setErrors($validator->errors());
            return false;
        }

        return parent::save($options);
    }
}
