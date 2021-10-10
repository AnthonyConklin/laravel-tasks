<?php

namespace AnthonyConklin\LaravelTasks\Transformers;

use AnthonyConklin\LaravelTasks\
Exceptions\NonExistentRelationException;
use AnthonyConklin\LaravelTasks\
Exceptions\NullResourceException;

class ValidatesRelations {

    /**
     * @param $resource
     * @param $relation
     * @throws NonExistentRelationException
     * @throws NullResourceException
     */
    public static function validate($resource, $relation) {
        if (is_null($resource)) {
            throw new NullResourceException();
        }
        if (!method_exists($resource, $relation)) {
            $message = sprintf('%s relationship not found on %s', ucfirst($relation), get_class($resource));
            throw new NonExistentRelationException($message);
        }
    }
}
