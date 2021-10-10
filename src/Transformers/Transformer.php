<?php

namespace AnthonyConklin\LaravelTasks\Transformers;

use Illuminate\Support\Facades\Auth;
use League\Fractal;

class Transformer extends Fractal\TransformerAbstract
{
    use ConditionallyLoadsAttributes;

    protected $resource;
    protected $includes;
    protected $excludes;

    protected $namespace = 'root';

    public function __construct($includes = [], $excludes = [])
    {
        $this->setIncludes($includes);
        $this->setExcludes($excludes);
    }

    public function setIncludes($includes = [])
    {
        $this->includes = array_merge($this->getRequestIncludes(), $includes);

        return $this;
    }

    public function getRequestIncludes()
    {
        $requestIncludes = request('include', '');
        $requestIncludes = str_replace(' ', '', $requestIncludes);

        return explode(',', $requestIncludes);
    }

    public function getIncludes()
    {
        return $this->includes;
    }

    public function hasInclude($key)
    {
        return in_array($key, $this->includes);
    }

    public function setExcludes($excludes = [])
    {
        $this->excludes = array_merge($this->getRequestExcludes(), $excludes);

        return $this;
    }

    public function getRequestExcludes()
    {
        $requestExcludes = request('exclude', '');
        $requestExcludes = str_replace(' ', '', $requestExcludes);

        return explode(',', $requestExcludes);
    }

    public function getExcludes()
    {
        return $this->excludes;
    }

    public function hasExclude($key)
    {
        return in_array($key, $this->excludes);
    }

    /**
     * @return array
     */
    protected function validParams()
    {
        return [
            'limit',
            'order',
        ];
    }

    /**
     * Automatically filter and transform array values.
     *
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function transform($data)
    {
        if (! method_exists($this, 'toArray')) {
            throw new \Exception('Transformer must include a "toArray" method.');
        }
        $this->resource = $data;

        $result = $this->toArray($data);
        if ($data instanceof Model && ! array_key_exists('_type', $result)) {
            $result = array_merge($result, [
                '_type' => $data->getMorphClass(),
            ]);
        }

        return $this->filter($result);
    }

    /**
     * @param array $includes
     * @return $this
     */
    public function forceInclude(array $includes = [])
    {
        $this->defaultIncludes = array_merge($this->defaultIncludes, $includes);

        return $this;
    }

    /**
     * @param Fractal\ParamBag $params
     * @param bool             $allowedParams
     *
     * @return $this
     * @throws \Exception
     */
    protected function validateParams(Fractal\ParamBag $params, $allowedParams = false)
    {
        $validParams = $allowedParams ? $allowedParams : $this->validParams();

        // Optional params validation
        $usedParams = array_keys(iterator_to_array($params));
        if ($invalidParams = array_diff($usedParams, $validParams)) {
            throw new \Exception(sprintf(
                'Invalid param(s): "%s". Valid param(s): "%s"',
                implode(',', $usedParams),
                implode(',', $validParams)
            ));
        }

        return $this;
    }

    protected function user()
    {
        return Auth::user();
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getNamespaceAttributes()
    {
        return [];
    }
}
