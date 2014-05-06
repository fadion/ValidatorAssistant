<?php namespace Fadion\ValidatorAssistant;

use Fadion\ValidatorAssistant\Bindings;
use Fadion\ValidatorAssistant\Subrules;
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
    * @var array Validation messages
    */
    protected $messages = array();

    /**
    * @var \Illuminate\Validation\Validator Validator instance
    */
    private $validator;

    /**
    * @var mixed Input(s) to be validated
    */
    private $inputs;

    /**
    * @var array Rules after the scope is resolved
    */
    private $finalRules;

    /**
    * Initialize the ValidatorAssistant class.
    *
    * @param  array  $inputs
    * @return void
    */
    public function __construct($inputs = null)
    {
        $this->inputs = $inputs ?: Input::all();
        $this->finalRules = $this->rules;
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

        $this->validator = Validator::make($this->inputs, $this->finalRules, $this->messages);
        $this->validator->setAttributeNames($this->attributes);
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
        $this->finalRules = $this->resolveScope($scope);

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
        // Keep the scopes as an array, even
        // when a single string is passed.
        if (! is_array($scope)) $scope = array($scope);

        // Add the base rules for later merging.
        $scopedRules = array($this->rules);

        foreach ($scope as $s)
        {
            $name = 'rules'.ucfirst($s);

            // The scoped rules must exist as a
            // class property.
            if (isset($this->$name))
            {
                $scopedRules[] = $this->$name;
            }
        }

        // Return an array with the merged rules.
        return call_user_func_array('array_merge', $scopedRules);
    }

    /**
    * Runs the Subrules class to resolve
    * subrules.
    *
    * @return void
    */
    private function resolveSubrules()
    {
        $subrules = new Subrules($this->inputs, $this->finalRules, $this->messages);

        $this->finalRules = $subrules->rules();
        $this->messages = $subrules->messages();
        $this->inputs = $subrules->inputs();
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
        $this->finalRules[$rule] = $value;

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
        if (isset($this->finalRules[$rule]))
        {
            $existing = $this->finalRules[$rule];

            // String rules are transformed into an array,
            // so they can be easily merged. Laravel's Validator
            // accepts rules as arrays or strings.
            if (! is_array($existing)) $existing = explode('|', $existing);
            if (! is_array($value)) $value = explode('|', $value);

            $this->finalRules[$rule] = implode('|', array_unique(array_merge($existing, $value)));
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
            $bindings = new Bindings(func_get_args(), $this->finalRules);
            $this->finalRules = $bindings->rules();
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
            
            $bindings = new Bindings(array(array($name => $args[0])), $this->finalRules);
            $this->finalRules = $bindings->rules();

            return $this;
        }
    }

}