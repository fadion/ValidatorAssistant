<?php namespace Fadion\ValidatorAssistant;

class Aliases
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
    * @var array Validation messages
    */
    private $messages = array();

    /**
     * @var string Alias delimiter
     */
    private $delimiter = ':';

    /**
    * Initialize the Aliases class.
    *
    * @param  array  $inputs
    * @param  string  $scope
    * @return void
    */
    public function __construct($inputs, $rules, $messages)
    {
        $this->inputs = $inputs;
        $this->rules = $rules;
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
    * Get the inputs after aliases have
    * been processed.
    *
    * @return array
    */
    public function inputs()
    {
        return $this->inputs;
    }

    /**
    * Get the rules after aliases have
    * been processed.
    *
    * @return array
    */
    public function rules()
    {
        return $this->rules;
    }

    /**
    * Get the messages after aliases have
    * been processed.
    *
    * @return array
    */
    public function messages()
    {
        return $this->messages;
    }

    /**
    * Processes aliases for rules and their respective
    * inputs and messages.
    *
    * @return void
    */
    private function process()
    {
        $inputs = $this->inputs;
        $rules = $this->rules;
        $messages = $this->messages;

        foreach ($rules as $name => $subRules)
        {
            if (strpos($name, $this->delimiter) !== false)
            {
                list($ruleName, $alias) = explode($this->delimiter, $name);

                $rules = $this->swapKey($rules, $name, array($alias => $subRules));
                $inputs = $this->swapKey($inputs, $ruleName, array($alias => $inputs[$ruleName]));
                $messages = $this->fixMessages($messages, $ruleName, $alias);

                unset($rules[$name]);
                unset($inputs[$ruleName]);
            }
        }

        $this->inputs = $inputs;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
    * Swaps an array key with a new element by
    * keeping the old position.
    *
    * @param array $array
    * @param string $name
    * @param array $newElement
    * @return array
    */
    private function swapKey($array, $name, Array $newElement)
    {
        $key = array_search($name, array_keys($array));

        $array = array_slice($array, 0, $key, true) +
                 $newElement +
                 array_slice($array, $key, count($array) - $key, true);

        return $array;
    }

    /**
    * Fixes error messages to reflect aliases.
    *
    * @param array $messages
    * @param string $name
    * @param string $alias
    * @return array
    */
    private function fixMessages($messages, $name, $alias)
    {
        foreach ($messages as $rule => $message)
        {
            if (strpos($rule, $name.'.') !== false)
            {
                $newRule = substr($rule, strpos($rule, '.') + 1);
                $messages[$alias.'.'.$newRule] = $message;

                unset($messages[$rule]);
            }
        }

        return $messages;
    }

}
