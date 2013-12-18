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
    public function __construct($inputs = null, $scope = null)
    {
        $this->inputs = $inputs ?: \Input::all();
        $this->rulesSubset = $this->resolveScope($scope);

        if (! $this->rulesSubset)
        {
            throw new \Exception('No validation rules found');
        }

        $this->fixSubRules();
    }
    
    /**
     * Static shorthand for creating a new validator.
     * 
     * @param  array  $inputs
     * @param  string  $scope
     * @return ValidatorAssistant
     */
    public static function make($inputs = null, $scope = null)
    {
        return new static($inputs, $scope);
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
     * Appends a rule to an existing set.
     *
     * @param string $rule
     * @param mixed $value
     * @return bool
     */
    public function appendRule($rule, $value)
    {
        if (! isset($this->rulesSubset[$rule]))
        {
            return false;
        }

        $subset = $this->rulesSubset[$rule];

        if (! is_array($subset))
        {
            $subset = explode('|', $subset);
        }

        if (! is_array($value))
        {
            $value = explode('|', $value);
        }

        $this->rulesSubset[$rule] = array_unique(array_merge($subset, $value));

        return true;
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
     * Binds a rule parameter.
     *
     * @return void
     */
    public function bind()
    {
        if (func_num_args())
        {
            $this->prepareBindings(func_get_args());
        }
    }

    /**
     * Sets a rule dynamically to the current scope.
     *
     * @param string $scope
     * @return array|false
     */
    private function resolveScope($scope)
    {
        $scope = ucfirst($scope);

        // Scope not required.
        // Return the 'default' scope.
        if (is_null($scope) and isset($this->rules))
        {
            return $this->rules;
        }
        // Scope set and a default ruleset exists.
        // Return the two as a merged array.
        elseif (isset($this->{'rules'.$scope}) and isset($this->rules))
        {
            return array_merge($this->rules, $this->{'rules'.$scope});
        }
        // Scope set but no default exists.
        // Return only the scope ruleset.
        elseif (isset($this->{'rules'.$scope}))
        {
            return $this->{'rules'.$scope};
        }

        return false;
    }

    /**
     * Prepares binding parameters.
     *
     * @param array $args
     * @return void
     */
    private function prepareBindings($args)
    {
        $bindings = [];

        // Two parameters (key, value).
        if (count($args) == 2)
        {
            $bindings[$args[0]] = $args[1];
        }
        // Array of parameters.
        elseif (is_array($args[0]))
        {
            $bindings = $args[0];
        }
        // Multiple key-value parameters.
        elseif (count($args) % 2 == 0)
        {
            for ($i = 0, $count = count($args); $i < $count; $i++)
            {
                if ($i % 2 == 0)
                {
                    $bindings[$args[$i]] = $args[$i + 1];
                }
            }
        }

        if (count($bindings))
        {
            $this->replaceBindings($bindings);
        }
    }

    /**
     * Replaces binding occurrences.
     *
     * @param array $bindings
     * @return void
     */
    private function replaceBindings($bindings)
    {
        $search = array_keys($bindings);
        $replace = array_values($bindings);

        array_walk($search, function(&$value, $key)
        {
            $value = '{'.$value.'}';
        });

        array_walk_recursive($this->rulesSubset, function(&$value, $key) use($search, $replace)
        {
            $value = str_ireplace($search, $replace, $value);
        });
    }

    /**
     * Resolves subrules.
     *
     * @param array $bindings
     * @return void
     */
    private function fixSubRules()
    {
        $rules = $this->rulesSubset;
        $inputs = $this->inputs;
        $messages = $this->messages;

        foreach ($rules as $name => $rule)
        {
            // Check for dot syntax.
            if (strpos($name, '.') !== false)
            {
                $newName = substr($name, 0, strrpos($name, '.'));
                $sub = substr($name, strrpos($name, '.') + 1);

                // The subrule should exist in the input data
                // and be an array.
                if (isset($inputs[$newName]) and is_array($inputs[$newName]))
                {
                    unset($rules[$name]);

                    // Prepare rules and inputs for an "*" (all) modifier.
                    if ($sub == '*')
                    {
                        $subInputs = $inputs[$newName];
                        unset($inputs[$newName]);

                        if (isset($messages[$name]))
                        {
                            $subMessage = $messages[$name];
                            unset($messages[$name]);
                        }

                        foreach ($subInputs as $subKey => $subValue)
                        {
                            $rules[$newName.'_'.$subKey] = $rule;
                            $inputs[$newName.'_'.$subKey] = $subValue;

                            $messages[$newName.'_'.$subKey] = $subMessage;
                        }
                    }
                    // Prepare rules and inputs for a named subrule.
                    elseif (isset($inputs[$newName][$sub]))
                    {
                        $rules[$newName.'_'.$sub] = $rule;
                        $inputs[$newName.'_'.$sub] = $inputs[$newName][$sub];

                        unset($inputs[$newName][$sub]);

                        if (isset($messages[$name]))
                        {
                            $messages[$newName.'_'.$sub] = $messages[$name];

                            unset($messages[$name]);
                        }

                        if (! count($inputs[$newName]))
                        {
                            unset($inputs[$newName]);
                        }
                    }
                }
            }
        }

        $this->rulesSubset = $rules;
        $this->inputs = $inputs;
        $this->messages = $messages;
    }

    /**
     * Handle calls to inexistant methods.
     */
    public function __call($name, $args)
    {
        // Dynamic binding calls.
        if (strpos($name, 'bind') !== false and count($args) == 1)
        {
            $name = strtolower(substr($name, strlen('bind')));
            $this->replaceBindings(array($name => $args[0]));
        }
    }

}
