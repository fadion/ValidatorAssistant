<?php namespace Fadion\ValidatorAssistant;

class ValidatorAssistant
{

    /**
     * @var array Validation rules
     */
    protected $rules = [];

    /**
     * @var array Validation messages
     */
    protected $messages = [];

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
        $this->rulesSubset = $this->resolveScope($scope, $this->rules);

        if (! $this->rulesSubset)
        {
            throw new \Exception('No validation rules found');
        }

        $this->validate();
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
        return $this->validator->fails();
    }

    /**
     * Checks if validation passed.
     *
     * @return bool
     */
    public function passes()
    {
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

        $this->validate();
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

        $this->validate();
    }

    /**
     * Sets a rule dynamically to the current scope.
     *
     * @param string $scope
     * @param array $rules
     * @return array|false
     */
    protected function resolveScope($scope, $rules)
    {
        if (count($rules) == 1)
        {
            return $rules;
        }
        elseif ((is_null($scope) or $scope == 'default') and isset($rules['default']))
        {
            return $rules['default'];
        }
        elseif (isset($rules[$scope]) and isset($rules['default']))
        {
            return array_merge($rules['default'], $rules[$scope]);
        }

        return false;
    }

}