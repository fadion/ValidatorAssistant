<?php namespace Fadion\ValidatorAssistant;

class Subrules
{

    /**
    * @var mixed Input(s) to be validated
    */
    private $inputs;

    /**
    * @var array Validation rules
    */
    private $rules;

    /**
    * @var array Custom attributes
    */
    private $attributes;

    /**
    * @var array Filters
    */
    private $filters;

    /**
    * @var array Validation messages
    */
    private $messages = array();

    /**
     * @var array Subrule delimiters
     */
    private $delimiters = array('[', ']');

    /**
     * @var string Message key to be removed
     */
    private $messageKey = null;

    /**
    * Initialize the Subrules class.
    *
    * @param  array  $inputs
    * @param  string  $scope
    * @return void
    */
    public function __construct($inputs, $rules, $attributes, $filters, $messages)
    {
        $this->inputs = $inputs;
        $this->rules = $rules;
        $this->attributes = $attributes;
        $this->filters = $filters;
        $this->messages = $messages;

        $this->process();
    }

    /**
    * Static factory.
    *
    * @param  array  $inputs
    * @param  string  $scope
    * @return void
    */
    public static function make($inputs, $rules, $messages)
    {
        return new static($inputs, $rules, $messages);
    }

    /**
    * Get the inputs after subrules have
    * been processed.
    *
    * @return array
    */
    public function inputs()
    {
        return $this->inputs;
    }

    /**
    * Get the rules after subrules have
    * been processed.
    *
    * @return array
    */
    public function rules()
    {
        return $this->rules;
    }

    /**
    * Get the attributes after subrules have
    * been processed.
    *
    * @return array
    */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
    * Get the filters after subrules have
    * been processed.
    *
    * @return array
    */
    public function filters()
    {
        return $this->filters;
    }

    /**
    * Get the messages after subrules have
    * been processed.
    *
    * @return array
    */
    public function messages()
    {
        return $this->messages;
    }

    /**
    * Processes subrules and it's associated
    * messages and inputs.
    *
    * @return void
    */
    private function process()
    {
        $inputs = $this->inputs;
        $rules = $this->rules;
        $attributes = $this->attributes;
        $filters = $this->filters;
        $messages = $this->messages;

        foreach ($rules as $name => $rule)
        {
            // Check for a subrule syntax: rule[key].
            if (preg_match('/(.+)'.preg_quote($this->delimiters[0]).'(.+)'.preg_quote($this->delimiters[1]).'/', $name, $matches))
            {
                $realName = $matches[1];
                $subName = $matches[2];

                if (! isset($inputs[$realName]) or ! is_array($inputs[$realName])) continue;

                // An all modifier is found: rule[*].
                if ($subName == '*')
                {
                    $subInputs = $inputs[$realName];

                    foreach ($subInputs as $subKey => $subValue)
                    {
                        $inputs[$realName.'_'.$subKey] = $subValue;

                        $rules = $this->fixRules($rules, $rule, $name, $realName, $subKey);
                        $messages = $this->fixMessages($messages, $name, $realName, $subKey);

                        if (isset($attributes[$name]))
                        {
                            $attributes = $this->fixRules($attributes, $attributes[$name], $name, $realName, $subKey);
                        }

                        if (isset($filters[$name]))
                        {
                            $filters = $this->fixRules($filters, $filters[$name], $name, $realName, $subKey);
                        }
                    }

                    $messages = $this->removeMessage($messages);

                    unset($inputs[$realName]);
                    unset($rules[$name]);

                    if (isset($attributes[$name]))
                    {
                        unset($attributes[$name]);
                    }

                    if (isset($filters[$name]))
                    {
                        unset($filters[$name]);
                    }
                }
                // A specific subrule is found.
                elseif (isset($inputs[$realName][$subName]))
                {
                    $rules[$realName.'_'.$subName] = $rule;
                    $inputs[$realName.'_'.$subName] = $inputs[$realName][$subName];
                    $rules = $this->fixRules($rules, $rule, $name, $realName, $subName);

                    if (isset($attributes[$name]))
                    {
                        $attributes = $this->fixRules($attributes, $attributes[$name], $name, $realName, $subName);
                        unset($attributes[$name]);
                    }

                    if (isset($filters[$name]))
                    {
                        $filters = $this->fixRules($filters, $filters[$name], $name, $realName, $subName);
                        unset($filters[$name]);
                    }

                    unset($rules[$name]);
                    unset($inputs[$realName][$subName]);

                    if (! count($inputs[$realName]))
                    {
                        unset($inputs[$realName]);
                    }

                    $messages = $this->fixMessages($messages, $name, $realName, $subName);
                    $messages = $this->removeMessage($messages);
                }
            }
        }
        
        $this->inputs = $inputs;
        $this->rules = $rules;
        $this->attributes = $attributes;
        $this->filters = $filters;
        $this->messages = $messages;
    }

    /**
    * Fixes error messages to reflect subrules
    * names.
    *
    * @param array $messages
    * @param string $name
    * @param string $realName
    * @param string $subName
    * @return array
    */
    private function fixMessages($messages, $name, $realName, $subName)
    {
        $toRemove = null;

        foreach ($messages as $messageRule => $message)
        {
            if (strpos($messageRule, $name.'.') !== false)
            {
                $toRemove = $messageRule;
                $messageRule = substr($messageRule, strpos($messageRule, '.') + 1);

                $messages[$realName.'_'.$subName.'.'.$messageRule] = $message;
            }
        }

        if (isset($toRemove))
        {
            $this->messageKey = $toRemove;
        }

        return $messages;
    }

    /**
    * Removes a set message key.
    *
    * @param array $messages
    * @return array
    */
    private function removeMessage($messages)
    {
        if (! is_null($this->messageKey))
        {
            unset($messages[$this->messageKey]);
            $this->messageKey = null;
        }

        return $messages;
    }

    /**
    * Modifies the ruleset so that dynamically created
    * subrules are added to their original position.
    *
    * @param array $rules
    * @param string $rule
    * @param string $name
    * @param string $realName
    * @param string $subName
    * @return array
    */
    private function fixRules($rules, $rule, $name, $realName, $subName)
    {
        if (isset($rules[$name]))
        {
            $key = array_search($name, array_keys($rules));

            $rules = array_slice($rules, 0, $key, true) +
                        array($realName.'_'.$subName => $rule) +
                        array_slice($rules, $key, count($rules) - $key, true);
        }

        return $rules;
    }

}
