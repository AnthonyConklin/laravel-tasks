<?php

namespace AnthonyConklin\LaravelTasks\Responses;

use AnthonyConklin\LaravelTasks\XmlOutput;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\TransformerAbstract;
use Psy\Util\Json;

class Response extends \Illuminate\Http\Response
{
    protected $serializer;

    protected $xmlNamespace;

    protected $xmlNamespaceAttributes = [];

    /**
     * @var bool
     */
    protected $outputXml = false;

    /**
     * @param       $resource
     * @param       $transformer
     * @param array $meta
     *
     * @return Response
     * @throws Exception
     */
    public function with($resource, $transformer, $meta = [])
    {
        if ($resource instanceof BaseCollection) {
            return $this->collection($resource, $transformer, $meta);
        }
        return $this->item($resource, $transformer, $meta);
    }

    /**
     * @param ArraySerializer $serializer
     *
     * @return $this
     */
    public function setSerializer(ArraySerializer $serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * @return ArraySerializer
     */
    public function getSerializer()
    {
        return $this->serializer ? $this->serializer : new ArraySerializer();
    }

    /**
     * @param $resource
     *
     * @return $this
     * @throws Exception
     */
    protected function transform($resource)
    {
        $manager = new Manager();
        $manager->setSerializer($this->getSerializer());
        $manager->parseIncludes($resource->getTransformer()->getIncludes());
        $manager->parseExcludes($resource->getTransformer()->getExcludes());

        $data = $manager->createData($resource)->toArray();
        if ($this->expectsXml()) {
            $attributes = $this->getXmlNamespaceAttributes();
            $data = array_merge($data, count($attributes) ? [
                '@attributes' => $attributes,
            ] : []);
            $data = XmlOutput::createXML($this->getXmlNamespace(), $data);
            $this->header('Content-Type', 'application/xml');
        }
        $this->setContent($data);
        return $this;
    }

    /**
     * @return bool
     */
    protected function expectsXml()
    {
        if (request()->header('Accept') === 'application/xml') {
            return true;
        }
        return $this->outputXml;
    }

    /**
     * @param string $xmlNamespace
     * @param array  $attributes
     *
     * @return $this
     */
    public function asXml($xmlNamespace = 'root', array $attributes = [])
    {
        $this->outputXml = true;
        $this->setXmlNamespace($xmlNamespace, $attributes);
        return $this;
    }

    /**
     * @param       $namespace
     * @param array $attributes
     *
     * @return $this
     */
    public function setXmlNamespace($namespace, array $attributes = [])
    {
        $this->xmlNamespace = $namespace;
        $this->setXmlNamespaceAttributes($attributes);
        return $this;
    }

    /**
     * @param $attributes
     *
     * @return $this
     */
    public function setXmlNamespaceAttributes($attributes)
    {
        $this->xmlNamespaceAttributes = $attributes;
        return $this;
    }

    /**
     * @return array
     */
    public function getXmlNamespaceAttributes()
    {
        return $this->xmlNamespaceAttributes;
    }

    public function getXmlNamespace()
    {
        return $this->xmlNamespace ?? 'root';
    }

    /**
     * @param       $resource
     * @param       $transformer
     * @param array $meta
     *
     * @return Response
     * @throws Exception
     */
    public function item($resource, $transformer, $meta = [])
    {
        if (is_string($transformer)) {
            $transformer = app($transformer);
        }

        if (!($transformer instanceof TransformerAbstract)) {
            throw new Exception('Must provide a valid transformer that extends ' . TransformerAbstract::class);
        }

        $resource = new Item($resource, $transformer);

        $resource->setMeta($meta);

        return $this->transform($resource);
    }

    /**
     * @param array $data
     * @param int   $statusCode
     *
     * @return Response
     */
    public function data($data = [], $statusCode = 200)
    {
        return $this->setContent($data)->setStatusCode($statusCode);
    }

    /**
     * @param       $resource
     * @param       $transformer
     * @param array $meta
     *
     * @return Response
     * @throws Exception
     */
    public function collection($resource, $transformer, $meta = [])
    {
        if (is_string($transformer)) {
            $transformer = app($transformer);
        }

        if (!($transformer instanceof TransformerAbstract)) {
            throw new Exception('Must provide a valid transformer that extends ' . TransformerAbstract::class);
        }

        if ($resource instanceof LengthAwarePaginator) {
            // strip collection from paginator

            $pagination = $resource->toArray();
            $pagination['type'] = 'paged';
            $pagination['links'] = [
                'path'           => $pagination['path'],
                'first_page_url' => $pagination['first_page_url'],
                'last_page_url'  => $pagination['last_page_url'],
                'next_page_url'  => $pagination['next_page_url'],
                'prev_page_url'  => $pagination['prev_page_url'],
            ];
            unset($pagination['data']);
            foreach ($pagination as $key => $value) {
                if (strstr($key, '_url') !== false) {
                    unset($pagination[$key]);
                }
            }
            // merge paginator meta with custom meta provided.
            $meta = array_merge($meta, [
                'pagination' => $pagination,
            ]);
            $resource = $resource->items();
        }

        $resource = new Collection($resource, $transformer);

        $resource->setMeta($meta);

        return $this->transform($resource);
    }

    protected function getLinks($resource)
    {
        $pagination = Arr::only($resource->toArray(), [
            'first_page',
            'current_page',
            'next_page',
            'last_page',
            'previous_page',
            'previous_page_url',
            'first_page_url',
            'next_page_url',
            'last_page_url',
        ]);
        return [
            'first'    => [
                'cursor' => $pagination['first_page'],
                'url'    => $pagination['first_page_url'],
            ],
            'previous' => [
                'cursor' => $pagination['previous_page'],
                'url'    => $resource->url($pagination['previous_page']),
            ],
            'current'  => [
                'cursor' => $pagination['current_page'],
                'url'    => $resource->url($pagination['current_page']),
            ],
            'next'     => [
                'cursor' => $pagination['next_page'],
                'url'    => $pagination['next_page_url'],
            ],
            'last'     => [
                'cursor' => $pagination['last_page'],
                'url'    => $pagination['last_page_url'],
            ],
        ];
    }

    public function withError($message, $code = 500, $info = [])
    {
        return $this->morphToJson([
            'error' => [
                'code'    => $code,
                'message' => $message,
                'info'    => $info,
            ],
        ]);
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function withSuccess($code = 200)
    {
        $this->setStatusCode($code);
        return $this;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function withAccepted($code = 202)
    {
        return $this->withSuccess($code);
    }
}
