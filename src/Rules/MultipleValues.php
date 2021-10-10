<?php

namespace AnthonyConklin\LaravelTasks\Rules;



use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\Relation;

class MultipleValues implements Rule
{

    private $values;

    private $invalid = [];

    /**
     * Create a new rule instance.
     *
     * @param array $validValues
     */
    public function __construct(array $validValues)
    {
        $this->values = $validValues;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $values = explode(',', $value);
        array_map(function ($value) {
            return trim($value);
        }, $values);
        $passes = true;
        foreach ($values as $value) {
            if (!in_array($value, $this->values)) {
                $passes = false;
                $this->invalid[] = $value;
            }
        }
        return $passes;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The following values are not supported: ' . implode(', ', $this->invalid);
    }

    // Quick Accessor
    public static function in(array $validValues)
    {
        return new static($validValues);
    }
}
