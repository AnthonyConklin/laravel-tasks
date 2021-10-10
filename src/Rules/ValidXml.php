<?php

namespace AnthonyConklin\LaravelTasks\Rules;

use Illuminate\Contracts\Validation\Rule;
use SimpleXMLElement;

class ValidXml implements Rule
{
    protected $rootNode;

    protected $rootNodeCorrect = true;

    /**
     * Create a new rule instance.
     *
     * @param $rootNode
     */
    public function __construct($rootNode = false)
    {
        $this->rootNode = $rootNode;
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
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($value);
        if ($xml === false || ! ($xml instanceof SimpleXMLElement)) {
            return false;
        }

        if ($this->rootNode && $xml->getName() !== $this->rootNode) {
            $this->rootNodeCorrect = false;

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ! $this->rootNodeCorrect ? sprintf('Root node does not match expected: "%s"', $this->rootNode) : 'Payload is not valid XML!';
    }
}
