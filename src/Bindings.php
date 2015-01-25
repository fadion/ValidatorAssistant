<?php namespace Fadion\ValidatorAssistant;

class Bindings {

    /**
    * @var array Validation rules
    */
    private $rules;

    /**
     * @var array Binding delimiters
     */
    private $delimiters = array('{', '}');

    /**
    * Initialize the Bindings class.
    *
    * @param  array  $inputs
    * @param  string  $scope
    * @return void
    */
    public function __construct($args, $rules)
    {
        $this->rules = $rules;
        $bindings = $this->prepare($args);

        if (count($bindings)) {
            $this->replace($bindings);
        }
    }

    /**
    * Static factory.
    *
    * @param  array  $inputs
    * @param  string  $scope
    * @return void
    */
    public static function make($args, $rules)
    {
        return new static($args, $rules);
    }

    /**
    * Get the rules after bindings have
    * been processed.
    *
    * @return array
    */
    public function rules()
    {
        return $this->rules;
    }

    /**
    * Prepares binding parameters.
    *
    * @param array $args
    * @return void
    */
    private function prepare($args)
    {
        $bindings = array();

        // Two parameters (key, value).
        if (count($args) == 2) {
            $bindings[$args[0]] = $args[1];
        }
        // Array of parameters.
        elseif (is_array($args[0])) {
            $bindings = $args[0];
        }

        return $bindings;
    }

    /**
    * Replaces binding occurrences.
    *
    * @param array $bindings
    * @return void
    */
    private function replace($bindings)
    {
        $search = array_keys($bindings);
        $replace = array_values($bindings);

        foreach ($search as $key => &$value) {
            $value = $this->delimiters[0].$value.$this->delimiters[1];
        }

        array_walk_recursive($this->rules, function(&$value, $key) use($search, $replace) {
            $value = str_ireplace($search, $replace, $value);
        });
    }

}
