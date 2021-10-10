<?php

namespace AnthonyConklin\LaravelTasks;

use AnthonyConklin\LaravelTasks\Responses\Response;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Validation\ValidationException;

abstract class LaravelTask implements Arrayable
{
    use HasAttributes;

    use GuardsAttributes;

    use HidesAttributes;

    use HasTimestamps;

    use ValidatesWhenResolvedTrait;

    public const CREATED_AT = false;
    public const UPDATED_AT = false;


    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'default';

    protected $limit = 15;

    protected $user;

    protected $account;

    protected $topic;

    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * @var bool
     */
    protected $validateOnConstruct = false;

    /**
     * DataRequest constructor.
     *
     * @param array $attributes
     * @param bool $validateImmediately
     *
     * @throws Exception
     */
    public function __construct($attributes = [], $validateImmediately = false)
    {
        $this->parseAttributes($attributes);

        if ($this->validateOnConstruct) {
            $this->validateResolved();
        }
    }

    /**
     * @param $request
     *
     * @return mixed
     */
    public static function filterInbound(Request $request)
    {
        return $request;
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    public static function filterOutbound(Response $response)
    {
        return $response;
    }

    /**
     * @return mixed
     */
    public static function getTopic()
    {
        return (new static())->topic;
    }

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public static function getRules($except = [])
    {
        $rules = (new static())->getRules();
        foreach ($except as $key) {
            unset($rules[$key]);
        }

        return $rules;
    }

    public function rules()
    {
        return [];
    }

    protected function user()
    {
        if ($this->user) {
            return $this->user;
        }

        return Auth::user();
    }

    /**
     * Quick helper for making external HTTP requests.
     *
     * @param array $config
     *
     * @return Client
     * @throws \InvalidArgumentException
     */
    protected function http($config = [])
    {
        return new Client($config);
    }

    abstract public function handle();

    protected function includes(array $data = [], Model $model = null, array $except = [])
    {
        $requested = explode(',', $this->get('include', ''));
        $requested = array_filter(array_merge($requested, $data));

        $requested = array_filter($requested, function ($relation) use ($except) {
            foreach ($except as $exceptRelation) {
                if (strstr($relation, $exceptRelation) !== false) {
                    return false;
                }
            }

            return true;
        });

        if ($model) {
            $requested = array_filter($requested, function ($relation) use ($model) {
                $relation = explode('.', $relation);

                return method_exists($model, $relation[0]);
            });
        }

        return $requested;
    }

    /**
     * @param          $request
     * @param \Closure $next
     *
     * @return mixed
     * @throws Exception
     */
    public function handlePipe($request, \Closure $next)
    {
        $response = $this->validate($request)->handle();
        $next($request);

        return $response;
    }

    /**
     * @param $attributes
     *
     * @return $this
     * @throws Exception
     */
    protected function parseAttributes($attributes)
    {
        if (is_array($attributes)) {
            $this->fill($attributes);
        } elseif ($attributes instanceof Request) {
            $this->parseFromRequest($attributes);
        } elseif ($attributes instanceof Arrayable) {
            $this->fill($attributes->toArray());
        } else {
            throw new Exception('Passed malformed data to transport.');
        }

        return $this;
    }

    /**
     * @return Pipeline
     */
    public function pipe()
    {
        return app(Pipeline::class)->via('handlePipe');
    }

    /**
     * @param array $attributes
     *
     * @return $this
     * @throws Exception
     */
    public function validate($attributes = [])
    {
        $this->parseAttributes($attributes);

        $this->validateResolved();

        return $this;
    }

    /**
     * @param $attributes
     *
     * @return Task
     * @throws Exception
     */
    public static function with($attributes)
    {
        return (new static($attributes))->validate();
    }

    public function parseFromRequest(Request $request)
    {
        $this->fill($request->all());

        return $this;
    }

    public function all()
    {
        return $this->attributesToArray();
    }

    public function has($field)
    {
        return boolval($this->getAttribute($field));
    }

    public function get($field, $default = null)
    {
        if (! $this->has($field)) {
            return $default;
        }

        return $this->getAttribute($field);
    }

    public function getRaw($field, $default = null)
    {
        if (! $this->has($field)) {
            return $default;
        }

        return $this->attributes[$field];
    }

    /**
     * Get a relationship.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getRelationValue($key)
    {
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     *
     * @return $this
     *
     * @throws MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        $validationFields = method_exists($this, 'rules') ? array_keys($this->rules()) : [];

        return array_merge($this->fillable, $validationFields);
    }

    /**
     * Fill the model with an array of attributes. Force mass assignment.
     *
     * @param array $attributes
     *
     * @return $this
     */
    public function forceFill(array $attributes)
    {
        return static::unguarded(function () use ($attributes) {
            return $this->fill($attributes);
        });
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $factory = app(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            $validator = $this->validator($factory);
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    /**
     * Create the default validator instance.
     *
     * @param \Illuminate\Contracts\Validation\Factory $factory
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        return $factory->make(
            $this->validationData(),
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    protected function validationData()
    {
        return $this->all();
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))->errorBag($this->errorBag)->redirectTo(url('/'));
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('This action is unauthorized.');
    }

    /**
     * Get the validated data from the request.
     *
     * @return array
     */
    public function validated()
    {
        return $this->validator->validated();
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Set the Validator instance.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     *
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the Redirector instance.
     *
     * @param \Illuminate\Routing\Redirector $redirector
     *
     * @return $this
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    protected function usesTimestamps()
    {
        return false;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * @param array $request
     *
     * @return mixed
     * @throws Exception
     */
    public function __invoke($request = [])
    {
        $this->validate($request);

        return $this->handle();
    }

    /**
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function run($params = [])
    {
        $task = new static();

        return $task($params);
    }
}
