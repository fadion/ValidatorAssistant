<?php namespace Fadion\ValidatorAssistant;

use Fadion\ValidatorAssistant\Bindings;
use Fadion\ValidatorAssistant\Subrules;
use Fadion\ValidatorAssistant\Filters;
use Input;
use Validator;

abstract class ValidatorAssistant
{

    /**
    * @var array Validation rules
    */
    protected $rules = array();

    /**
    * @var array Custom attributes
    */
    protected $attributes = array();

    /**
    * @var array Filters
    */
    protected $filters = array();

    /**
    * @var array Validation messages
    */
    protected $messages = array();

    /**
    * @var mixed Input(s) to be validated
    */
    protected $inputs;

    /**
    * @var \Illuminate\Validation\Validator Validator instance
    */
    private $validator;

    /**
    * @var array A copy of the validation rules
    */
    private $originalRules;

    /**
     * @var array A copy of the validation attributes
     */
    private $originalAttributes;

    /**
     * @var array A copy of the validation messages
     */
    private $originalMessages;

    /**
    * Initialize the ValidatorAssistant class.
    *
    * @param  array  $inputs
    * @return void
    */
    public function __construct($inputs = null)
    {
        $this->inputs = $inputs ?: Input::all();

        // Run the 'before' method, letting the
        // user execute code before validation.
        if (method_exists($this, 'before'))
        {
            $this->before();
        }

        $this->originalRules = $this->rules;
        $this->originalAttributes = $this->attributes;
        $this->originalMessages = $this->messages;
    }

    /**
    * Static factory.
    *
    * @param  array  $inputs
    * @return ValidatorAssistant
    */
    public static function make($inputs = null)
    {
        return new static($inputs);
    }

    /**
    * Checks if validation passed.
    *
    * @return bool
    */
    public function passes()
    {
        $this->validate();
        return $this->validator->passes();
    }

    /**
    * Checks if validation failed.
    *
    * @return bool
    */
    public function fails()
    {
        $this->validate();
        return $this->validator->fails();
    }

    /**
    * Run the validation using Laravel's Validator.
    *
    * @return void
    */
    private function validate()
    {
        // Try to resolve subrules if there are any
        // as a final step before running the validator.
        $this->resolveSubrules();

        // Apply input filters.
        $filters = new Filters($this->inputs, $this->filters);
        $this->inputs = $filters->apply();

        // Apply custom rules.
        $this->customRules();

        $this->validator = Validator::make($this->inputs, $this->rules, $this->messages);

        // Apply attributes.
        $this->validator->setAttributeNames($this->attributes);

        // Run the 'after' method, letting the
        // user execute code after validation.
        if (method_exists($this, 'after'))
        {
            $this->after($this->validator);
        }
    }

    /**
    * Get the inputs. Especially useful for getting
    * the filtered input values.
    *
    * @return array
    */
    public function inputs()
    {
        return $this->inputs;
    }

    /**
    * Get the Validator instance. Useful when passing
    * errors to a response.
    *
    * @return \Illuminate\Validation\Validator
    */
    public function instance()
    {
        return $this->validator;
    }

    /**
    * Get validation error messages.
    *
    * @return \Illuminate\Support\MessageBag
    */
    public function errors()
    {
        return $this->validator->messages();
    }

    /**
    * Alias of errors().
    *
    * @return \Illuminate\Support\MessageBag
    */
    public function messages()
    {
        return $this->validator->messages();
    }

    /**
    * Get the failed validation rules.
    *
    * @return array
    */
    public function failed()
    {
        return $this->validator->failed();
    }

    /**
    * Set the scope.
    *
    * @param string|array $scope
    * @return ValidatorAssistant
    */
    public function scope($scope)
    {
        $this->rules = $this->resolveScope($scope);
        $this->attributes = $this->resolveAttributes($scope);
        $this->messages = $this->resolveMessages($scope);

        return $this;
    }

    /**
    * Searches for scoped rules and merges them
    * with the default ones.
    *
    * @param string|array $scope
    * @return array
    */
    private function resolveScope($scope)
    {
        return $this->resolve($scope, 'rules');
    }

    /**
     * Searches for scoped attributes and merges them
     * with the default ones.
     *
     * @param string|array $scope
     * @return array
     */
    private function resolveAttributes($scope)
    {
        return $this->resolve($scope, 'attributes');
    }


    /**
     * Searches for scoped messages and merges them
     * with the default ones.
     *
     * @param string|array $scope
     * @return array
     */
    private function resolveMessages($scope)
    {
        return $this->resolve($scope, 'messages');
    }


    /**
     * Searches for scoped $property and merges them
     * with the default ones.
     *
     * @param string|array $scope
     * @param string|array $property
     * @return array
     */
    private function resolve($scope, $property)
    {
        // Keep the scopes as an array, even
        // when a single string is passed.
        if (! is_array($scope)) $scope = array($scope);

        // Add the base rules for later merging.
        $scoped = array($this->{'original'.ucfirst($property)});

        foreach ($scope as $s)
        {
            $name = $property.studly_case($s);

            // The scoped attributes must exist as a
            // class property.
            if (isset($this->$name))
            {
                $scoped[] = $this->$name;
            }
        }

        // Return an array with the merged rules.
        return call_user_func_array('array_merge', $scoped);
    }

    /**
    * Runs the Subrules class to resolve
    * subrules.
    *
    * @return void
    */
    private function resolveSubrules()
    {
        $subrules = new Subrules($this->inputs, $this->rules, $this->attributes, $this->filters, $this->messages);

        $this->rules = $subrules->rules();
        $this->attributes = $subrules->attributes();
        $this->filters = $subrules->filters();
        $this->messages = $subrules->messages();
        $this->inputs = $subrules->inputs();
    }

    /**
    * Applies custom rules.
    *
    * @return void
    */
    private function customRules()
    {
        // Get the methods of the calling class.
        $methods = get_class_methods(get_called_class());

        // Custom rule methods begin with "custom".
        $methods = array_filter($methods, function($var)
        {
            return strpos($var, 'custom') !== false && $var !== 'customRules';
        });

        if (count($methods))
        {
            foreach ($methods as $method)
            {
                $self = $this;

                // Convert camelCase method name to snake_case
                // custom rule name.
                $customRule = snake_case($method);
                $customRule = str_replace('custom_', '', $customRule);

                // Extend the validator using the return value
                // of the custom rule method.
                Validator::extend($customRule, function($attribute, $value, $parameters) use ($self, $method)
                {
                    return $self->$method($attribute, $value, $parameters);
                });
            }
        }
    }

    /**
    * Adds a rules dynamically. Will override
    * existing rules when a key already exists.
    *
    * @param string $rule
    * @param mixed $value
    * @return ValidatorAssistant
    */
    public function addRule($rule, $value)
    {
        $this->rules[$rule] = $value;

        return $this;
    }

    /**
    * Adds a message dynamically. Will override
    * existing messages when a key already exists.
    *
    * @param string $message
    * @param mixed $value
    * @return ValidatorAssistant
    */
    public function addMessage($message, $value)
    {
        $this->messages[$message] = $value;

        return $this;
    }

    /**
    * Appends a rule to an existing set.
    *
    * @param string $rule
    * @param mixed $value
    * @return ValidatorAssistant
    */
    public function append($rule, $value)
    {
        if (isset($this->rules[$rule]))
        {
            $existing = $this->rules[$rule];

            // String rules are transformed into an array,
            // so they can be easily merged. Laravel's Validator
            // accepts rules as arrays or strings.
            if (! is_array($existing)) $existing = explode('|', $existing);
            if (! is_array($value)) $value = explode('|', $value);

            $this->rules[$rule] = implode('|', array_unique(array_merge($existing, $value)));
        }

        return $this;
    }

    /**
    * Binds a rule parameter.
    *
    * @return ValidatorAssistant
    */
    public function bind()
    {
        if (func_num_args())
        {
            $bindings = new Bindings(func_get_args(), $this->rules);
            $this->rules = $bindings->rules();
        }

        return $this;
    }

    /**
    * Catch dynamic binding calls.
    */
    public function __call($name, $args)
    {
        if (strpos($name, 'bind') !== false and count($args) == 1)
        {
            $name = strtolower(substr($name, strlen('bind')));

            $bindings = new Bindings(array(array($name => $args[0])), $this->rules);
            $this->rules = $bindings->rules();

            return $this;
        }
    }

}