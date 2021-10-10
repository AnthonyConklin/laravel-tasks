<?php

namespace AnthonyConklin\LaravelTasks\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\Relation;

class ModelUuidExists implements Rule
{
    private $model;

    private $additionalWheres;

    /**
     * Create a new rule instance.
     *
     * @param $model
     * @param array $additionalWheres
     */
    public function __construct($model, array $additionalWheres = [])
    {
        $this->model = $this->checkForDynamicModel($model);
        $this->additionalWheres = $additionalWheres;
    }

    protected function checkForDynamicModel($model)
    {
        if (! is_string($model)) {
            return $model;
        }
        // Morph Model from alias
        $model = Relation::getMorphedModel($model) ?? false;
        if ($model) {
            $model = class_exists($model) ? new $model() : false;
        }

        return $model;
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
        if (! $this->model) {
            return false;
        }
        $model = $this->model->whereUuid($value);
        if (count($this->additionalWheres)) {
            foreach ($this->additionalWheres as $where) {
                $model = $where($model);
            }
        }

        return $model->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This ' . $this->model->getMorphClass() . ' does not exist!';
    }
}
