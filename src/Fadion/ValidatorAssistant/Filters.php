<?php namespace Fadion\ValidatorAssistant;

class Filters
{

    /**
    * @var array Filters
    */
    protected $filters = array();

    /**
    * @var mixed Inputs
    */
    private $inputs;

    /**
    * Initialize the Filters class.
    *
    * @param  array  $inputs
    * @param  string  $filters
    * @return void
    */
    public function __construct($inputs, $filters)
    {
        $this->inputs = $inputs;
        $this->filters = $filters;
    }

    /**
    * Apply filters to the inputs.
    *
    * @return array
    */
    public function apply()
    {
        $filters = $this->filters;
        $inputs = $this->inputs;

        if (count($filters))
        {
            foreach ($filters as $name => $filter)
            {
                $rules = explode('|', $filter);

                // At least a rule is set and the input
                // field exists.
                if (count($rules) and isset($inputs[$name]))
                {
                    foreach ($rules as $rule)
                    {
                        $rule = explode(':', $rule);

                        $argument = null;
                        if (isset($rule[1]))
                        {
                            $argument = $rule[1];
                        }

                        $rule = strtolower($rule[0]);
                        $rule = str_replace('_', ' ', $rule);
                        $rule = str_replace(' ', '', ucwords($rule));

                        $method = 'filter'.$rule;

                        // Check if rule is defined as a class method.
                        if (method_exists($this, $method))
                        {
                            $inputs[$name] = $this->$method($inputs[$name], $argument);
                        }
                    }
                }
            }
        }

        return $inputs;
    }

    /**************
    *   FILTERS   *
    **************/

    /**
    * Trim filter
    *
    * @param string $value
    * @return string
    */
    private function filterTrim($value, $argument = null)
    {
        return trim($value, $argument);
    }

    /**
    * Trim filter
    *
    * @param string $value
    * @return string
    */
    private function filterLrim($value, $argument = null)
    {
        return ltrim($value, $argument);
    }

    /**
    * Trim filter
    *
    * @param string $value
    * @return string
    */
    private function filterRtrim($value, $argument = null)
    {
        return rtrim($value, $argument);
    }

    /**
    * MD5 filter
    *
    * @param string $value
    * @return string
    */
    private function filterMd5($value, $argument = null)
    {
        return md5($value);
    }

    /**
    * Sha1 filter
    *
    * @param string $value
    * @return string
    */
    private function filterSha1($value, $argument = null)
    {
        return sha1($value);
    }

    /**
    * URL Encode filter
    *
    * @param string $value
    * @return string
    */
    private function filterUrlencode($value, $argument = null)
    {
        return urlencode($value);
    }

    /**
    * URL Decode filter
    *
    * @param string $value
    * @return string
    */
    private function filterUrldecode($value, $argument = null)
    {
        return urldecode($value);
    }

    /**
    * Strip Tags filter
    *
    * @param string $value
    * @return string
    */
    private function filterStripTags($value, $argument = null)
    {
        $allowedTags = '<p><a><b><i><em><strong><img><br><ul><ol><li><span><blockquote><code><sub><sup><h1><h2><h3><h4><h5><h6><dd><dl><label>';

        return strip_tags($value, $allowedTags);
    }

    /**
    * HTMLEntities filter
    *
    * @param string $value
    * @return string
    */
    private function filterHtmlentities($value, $argument = null)
    {
        return htmlentities($value, ENT_QUOTES, "UTF-8");
    }

    /**
    * Base64 Encode filter
    *
    * @param string $value
    * @return string
    */
    private function filterBase64Encode($value, $argument = null)
    {
        return base64_encode($value);
    }

    /**
    * Base64 Decode filter
    *
    * @param string $value
    * @return string
    */
    private function filterBase64Decode($value, $argument = null)
    {
        return base64_decode($value);
    }

    /**
    * Lcfirst filter
    *
    * @param string $value
    * @return string
    */
    private function filterLcfirst($value, $argument = null)
    {
        return lcfirst($value);
    }

    /**
    * Ucfirst filter
    *
    * @param string $value
    * @return string
    */
    private function filterUcfirst($value, $argument = null)
    {
        return ucfirst($value);
    }

    /**
    * Ucwords filter
    *
    * @param string $value
    * @return string
    */
    private function filterUcwords($value, $argument = null)
    {
        return ucfirst($value);
    }

    /**
    * Upper filter
    *
    * @param string $value
    * @return string
    */
    private function filterUpper($value, $argument = null)
    {
        if (extension_loaded('mbstring'))
        {
            return mb_strtoupper($value);
        }

        return strtoupper($value);
    }

    /**
    * Lower filter
    *
    * @param string $value
    * @return string
    */
    private function filterLower($value, $argument = null)
    {
        if (extension_loaded('mbstring'))
        {
            return mb_strtolower($value);
        }
        
        return strtolower($value);
    }

    /**
    * NL2BR filter
    *
    * @param string $value
    * @return string
    */
    private function filterNl2br($value, $argument = null)
    {
        return nl2br($value);
    }

    /**
    * Sanitize email filter
    *
    * @param string $value
    * @return string
    */
    private function filterSanitizeEmail($value, $argument = null)
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
    * Sanitize encoded filter
    *
    * @param string $value
    * @return string
    */
    private function filterSanitizeEncoded($value, $argument = null)
    {
        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
    * Sanitize string filter
    *
    * @param string $value
    * @return string
    */
    private function filterSanitizeString($value, $argument = null)
    {
        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
    * Sanitize url filter
    *
    * @param string $value
    * @return string
    */
    private function filterSanitizeUrl($value, $argument = null)
    {
        return filter_var($value, FILTER_SANITIZE_URL);
    }

}