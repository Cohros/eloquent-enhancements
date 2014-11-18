<?php

namespace Sigep\EloquentEnhancements\Traits;

use Illuminate\Support\MessageBag;

trait Error
{
    /**
     * Messages
     * @var MessageBag
     */
    protected $errors = null;

    /**
     * Retrieve error messages or a empty MessageBag if errors is not setted
     * 
     * @return MessageBag
     * 
     */
    public function errors()
    {
        if (is_null($this->errors)) {
            $this->errors = new MessageBag();
        }

        return $this->errors;
    }

    /**
     * Set errors
     * 
     * @param MessageBag $errors
     * 
     */
    public function setErrors(MessageBag $errors)
    {
        $this->errors = $errors;
    }
}
