<?php

namespace AnthonyConklin\LaravelTasks\Rules;



use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ValidModel implements Rule
{

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if($value instanceof Model){
            $value = $value->getMorphClass();
        }
        return !is_null(Relation::getMorphedModel($value));
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Type is not valid!';
    }
}
