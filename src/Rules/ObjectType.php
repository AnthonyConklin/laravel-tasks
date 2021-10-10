<?php

namespace AnthonyConklin\LaravelTasks\Rules;

use Illuminate\Contracts\Validation\ImplicitRule;

class ObjectType implements ImplicitRule
{
    protected $objectType = false;

    public function __construct($objectType = false)
    {
        $this->objectType = $objectType;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->objectType === false) {
            return is_object($value);
        }

        return $value instanceof $this->objectType;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if ($this->objectType === false) {
            return 'The :attribute must be an object';
        }
        $class = explode('\\', $this->objectType);
        $class = end($class);

        return sprintf('The :attribute parameter must be an instance of %s', $class);
    }
}
