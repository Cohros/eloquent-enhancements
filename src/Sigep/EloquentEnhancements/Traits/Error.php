<?php

namespace Sigep\EloquentEnhancements\Traits;

use Illuminate\Support\MessageBag;

trait Error
{
    protected $errors = null;

    public function errors()
    {
        if (is_null($this->errors)) {
            $this->errors = new MessageBag();
        }

        return $this->errors;
    }

    public function setErrors(MessageBag $errors)
    {
        $this->errors = $errors;
    }
}