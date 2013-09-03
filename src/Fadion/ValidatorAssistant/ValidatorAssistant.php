<?php namespace Fadion\ValidatorAssistant;

abstract class ValidatorAssistant
{

    /**
     * @var array Validation rules
     */
    protected $rules = array();

    /**
     * @var array Validation messages
     */
    protected $messages = array();

    /**
     * @var \Illuminate\Validation\Validator Validator instance
     */
    protected $validator;

    /**
     * @var mixed Input(s) to be validated
     */
    protected $inputs;

    /**
     * @var array Rule's subset under a scope
     */
    protected $rulesSubset;

    /**
     * Initialize the ValidatorAssistant class.
     *
     * @param  array  $inputs
     * @param  string  $scope
     * @return void
     */
    public function __construct($inputs, $scope = null)
    {
        $this->inputs = $inputs;
        $this->rulesSubset = $this->resolveScope($scope);

        if (! $this->rulesSubset)
        {
            throw new \Exception('No validation rules found');
        }
    }

    /**
     * Run the validation using Laravel's Validator.
     *
     * @return void
     */
    protected function validate()
    {
        $this->validator = \Validator::make($this->inputs, $this->rulesSubset, $this->messages);
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
     * Get validation error messages.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors()
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
     * Sets a rule dynamically to the current scope.
     *
     * @param string $rule
     * @param mixed $value
     * @return void
     */
    public function setRule($rule, $value)
    {
        $this->rulesSubset[$rule] = $value;
    }

    /**
     * Sets a message dynamically.
     *
     * @param string $rule
     * @param mixed $value
     * @return void
     */
    public function setMessage($message, $value)
    {
        $this->messages[$rule] = $value;
    }

    /**
     * Sets a rule dynamically to the current scope.
     *
     * @param string $scope
     * @param array $rules
     * @return array|false
     */
    protected function resolveScope($scope)
    {
        $rules = $this->rules;

        // No scope defined in rules.
        // Return the rules as is.
        if (count($rules) == 1)
        {
            return $rules;
        }
        // Scope not required or scope is 'default'.
        // Return the 'default' scope.
        elseif ((is_null($scope) or $scope == 'default') and isset($rules['default']))
        {
            return $rules['default'];
        }
        // Scope set and a default ruleset exists.
        // Return the two as a merged array.
        elseif (isset($rules[$scope]) and isset($rules['default']))
        {
            return array_merge($rules['default'], $rules[$scope]);
        }
        // Scopet set but no default exists.
        // Return only the scope ruleset.
        elseif (isset($rules[$scope]))
        {
            return $rules[$scope];
        }

        return false;
    }

}