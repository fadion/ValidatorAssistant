> ValidatorAssistant was recently tagged with a 1.0 version and finally has unit tests. Please update your composer requirements as instructed in the [Installation](#installation) section. The only breaking change is the rename of `inputs()` to `getInputs()`.

> To use it in Laravel 5, please see the `l5` branch.

# ValidatorAssistant

Keep Laravel Controllers thin and reuse code by organizing validation rules and messages into dedicated classes. ValidatorAssistant is a small library built to be extended by those classes, so they can easily use Laravel's Validation system and a few additional features like subrules, scopes and binding.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Sub Rules](#sub-rules)
- [Attribute Names](#attribute-names)
- [Filters](#filters)
- [Scoped Rules](#scoped-rules)
- [Dynamic Rules and Messages](#dynamic-rules-and-messages)
- [Parameter Binding](#parameter-binding)
- [Before and After Methods](#before-and-after-methods)
- [Custom Rules](#custom-rules)
- [Integrating Fadion/Rule](#integrating-fadionrule)

## Installation

- Add the package to your composer.json file and run `composer update`:
```json
{
    "require": {
        "fadion/validator-assistant": "~1.0"
    }
}
```
- (Optional) Add a new alias: `'ValidatorAssistant' => 'Fadion\ValidatorAssistant\ValidatorAssistant'` to your `app/config/app.php` file, inside the `aliases` array.

## Usage

ValidatorAssistant can be extended by any PHP class that follows just a few simple rules. As a personal preference, I use an `app/validators` folder to store validation classes and have added it to the `classmap` option of composer.json for simple autoloading. Probably it's a good idea to namespace them too.

A validation class in action is written below. Note the $rules and $messages properties. For them to be "seen" by ValidatorAssistant, their visibility needs to be `protected` or `public`, but not `private`.

```php
class UserValidator extends ValidatorAssistant {

    // Validation rules, as you'd define them
    // for Laravel's Validator class.
    protected $rules = array(
        'username' => 'required',
        'email' => 'required|email'
    );

    // Error messages.
    protected $messages = array(
        'username.required' => "Username is required",
        'email.required' => "Email is required",
        'email.email' => "Email is invalid"
    );

}
```

When namespaced, you would write it:

```php
namespace MyApp\Validators;

// If the alias was added
use ValidatorAssistant;

// Without alias
use Fadion\ValidatorAssistant\ValidatorAssistant;

class UserValidator extends ValidatorAssistant {}
```

With the rules and messages in the validation class defined, a typical workflow in a controller would be as follows:

```php
$userValidator = UserValidator::make(Input::all());

if ($userValidator->fails()) {
    return Redirect::back()->withInput()->withErrors($userValidator->instance());
}
```

You can even omit the `Input::all()` call, as it will be called by default from ValidatorAssistant:

```php
$userValidator = UserValidator::make();

// is the sames as
$userValidator = UserValidator::make(Input::all());
```

Pretty neat, right?! Whenever you'll need to validate a model or form, just call the appropriate validation class and you'll be done with a few lines of code.

## Sub Rules

Array inputs are often helpful for organizing big forms, localized fields, etc. Unfortunately, the Laravel Validator doesn't support sub rules for the time being, so if you're stuck, ValidatorAssistant will be really helpful.

Assuming you need to create some multi-lingual inputs, where a field will be treated as an array, the HTML code will be as follows:

```html
<input type="text" name="title[sq]">
<input type="text" name="title[en]">
<input type="text" name="title[it]">
```

Setting rules for each of those inputs is as simple as writing array keys.

```php
protected $rules = array(
    'title[sq]' => 'required',
    'title[en]' => 'max:15',
    'title[it]' => 'required|alpha'
);
```

The same applies to messages.

```php
protected $messages = array(
    'title[sq].required' => 'Albanian title is required.',
    'title[en].required' => 'English title is required.',
    'title[it].required' => 'Italian title is required.'
);
```

What ValidatorAssistant does in the background is run through all the rules, messages and inputs to modify them so they can be processed by Laravel's Validator. A `title[en]` rule is translated as `title_en` both for rules and inputs, and `title_en.required` for messages.

There's also a handy, catch-all modifier to validate all subrules of an input, which is useful when keys are build programatically and each of them has the same validation rules. In the languages case, the example above could be written as:

```php
protected $rules = array(
    'title[*]' => 'required'
);

protected $messages = array(
    'title[*].required' => 'The title is required.'
);
```

## Attribute Names

Laravel supports custom attribute names for fields, as an easy way to alias inputs and generate helpful error messages. ValidatorAssistant supports them too!

Just add an `$attribute` array as a class member of your validation class:

```php
protected $rules = array(
    'username' => 'required',
    'email' => 'email'
);

// Custom attributes
protected $attributes = array(
    'username' => 'Your name',
    'email' => 'Your email'
);

protected $messages = array(
    'username.required' => ':attribute is required.',
    'email.email' => ':attribute is not valid.'
);
```

Sub rules apply to attribute names in the same way as for rules and messages:

```php
protected $attributes = array(
    'title[sq]' => 'Titulli',
    'title[en]' => 'Title'
);

// or with the catch-all modifier

protected $attributes = array(
    'title[*]' => 'The title'
);
```

## Filters

There are occasions where input data needs to be transformed or sanitized in a certain way before validation. You can do it manually, or even easier, use filters. ValidatorAssistant's filters are defined exactly as rules, but with some specific keywords for each filter.

Take the following example:

```php
protected $rules = array(
    'title' => 'required',
    'body' => 'required'
);

protected $filters = array(
    'title' => 'trim|ucwords',
    'body' => 'strip_tags'
);
```

The "title" input will be filtered by using PHP's `trim()` and `ucwords()` functions, while the "body" input will be filtered by `strip_tags()`. Except for a few, most filters are their PHP function equivalent.

There are also some filters that accept paramenters. For example, `rtrim` accepts a string parameter with the characters to trim and `limit` accepts a parameter as the number of characters the string must be limited to:

```php
protected $filters = array(
    'title' => 'rtrim:abc',
    'body' => 'trim|limit:255'
);
```

As for anything else, sub rules can be set for filters too:

```php
protected $filters = array(
    'title[sq]' => 'trim|ucfirst',
    'title[en]' => 'trim|ucwords'
);

// or with the catch-all modifier

protected $filters = array(
    'title[*]' => 'trim|upper'
);
```

Finally, you can even get the filtered inputs back if you want to use the transformed values, for database entry or anything else. Just run the `inputs()` method on the validator object after validation has run.

```php
$userValidator = UserValidator::make(Input::all());

if ($userValidator->fails()) {
    return Redirect::back()->withInput()->withErrors($userValidator->instance());
}

// Will return the filtered input data
$inputs = $userValidator->inputs();
```

**The available filters are documented below:**

`trim:[optional characters to be trimed] => trim($input, $chars)`

`ltrim:[optional characters to be trimed] => ltrim($input, $chars)`

`rtrim:[optional characters to be trimed] => rtrim($input, $chars)`

`md5 => md5($input)`

`sha1 => sha1($input)`

`url_encode => url_encode($input)`

`url_decode => url_decode($input)`

`strip_tags => strip_tags($input)`

`htmlentities => htmlentities($input)`

`base64_encode => base64_encode($input)`

`base64_decode => base64_decode($input)`

`lcfirst => lcfirst($input)`

`ucfirst => ucfirst($input)`

`ucwords => ucwords($input)`

`upper => strtoupper($input)`

`lower => strtolower($input)`

`nl2br => nl2br($input)`

`date:[date format] => date($format, strtotime($input))`

`number_format:[decimals] => number_format($input, $decimals)`

`sanitize_email => filter_var($input, FILTER_SANITIZE_EMAIL)`

`sanitize_encoded => filter_var($input, FILTER_SANITIZE_ENCODED)`

`sanitize_string => filter_var($input, FILTER_SANITIZE_STRING)`

`sanitize_url => filter_var($input, FILTER_SANITIZE_URL)`

`limit:[number of characters] => limits a string to a number of characters`

`mask:[optional mask character] => masks a string with a mask character (default: *)`

`alpha => converts a string to alphabet characters only`

`alphanumeric => converts a string to alphanumeric characters only`

`numeric => converts a string to numeric characters only`

`intval => intval($input, $base)`

`floatval => floatval($input)`

`boolval => boolval($input)`

## Scoped Rules

For the same model or form, you may need to apply new rules or remove uneeded ones. Let's say that for the registration process, you just need the username and email fields, while for the profile form there are a bunch of others. Sure, you can build two different validation classes, but there's a better way. Scope!

You can define as many scopes as you like using simple PHP class properties. Look at the following example:

```php
// Default rules
protected $rules = array(
    'username' => 'required',
    'email' => 'required|email'
);

// Profile rules
protected $rulesProfile => array(
    'name' => 'required',
    'age' => 'required|numeric|min:13'
);

// Registration rules
protected $rulesRegister => array(
    'terms' => 'accepted'
);
```

Consider the "default" scope (class property $rules) as a shared ruleset that will be combined with any other scope you call. As a convention, scope names should be of the "rulesName" format (camelCase), otherwise it will fail to find the class property. For example: rulesLogin, rulesEdit or rulesDelete.

Now we'll initialize the validation class:

```php
// Validates the 'default' and 'profile' rules combined,
// with the 'profile' ruleset taking precedence
$userValidator = UserValidator::make(Input::all())->scope('profile');

// Validates the 'default' and 'register' rules
$userValidator = UserValidator::make(Input::all())->scope('register');

// Validates the 'default', 'profile' and 'register' rules
$userValidator = UserValidator::make(Input::all())->scope(['profile', 'register']);

// Validates the 'default' rules only
$userValidator = UserValidator::make(Input::all());
```

The default behaviour is to merge rules by replacing keys, so that scopes take precedence. This allows for more flexiblity and safer merging, as you can easily predict which rules will be computed.

The following ruleset:

```php
protected $rules = [
    'username' => 'required'
];

protected $rulesProfile = [
    'username' => 'unique:users'
];
```

Will produce the following rules when "profile" scope is selected, as scopes replace previous rules.

```php
[
    'username' => 'unique:users'
]
```

However, there may be scenarios when you'll need rules to be preserved, not replaced. To allow this, just add a class property in your validator classes:

```php
class UserValidator extends ValidatorAssistant {

    protected $preserveScopeValues = true;

}
```

The previous rules will be computed to:

```php
[
    'username' => 'required|unique:users'
]
```


## Dynamic Rules and Messages

In addition to the defined rules and messages, you can easily add dynamic ones when the need rises with the `addRule` and `addMessage` methods. This is a convenient functionality for those occassions when rules have to contain dynamic parameters or need to be added on the fly for certain actions.

```php
$userValidator = UserValidator::make(Input::all());

// New rules or messages will be added or overwrite existing ones
$userValidator->addRule('email', 'required|email|unique:users,email,10');
$userValidator->addMessage('email.unique', "Cmon!");
```

There's also the `append` method that instead of rewritting a ruleset, will append new rules to it. It works only on an existing ruleset, but will fail silently. Additionally, it will override rules of the same type with the new ones. Considering the previous example and supossing that the "email" field has already a "required" rule, we can append to it as follows:

```php
// The combined rules will be: required|email|unique:users
$userValidator->append('email', 'email|unique:users');
```

## Parameter Binding

As a completely different and [probably] more elegant approach to the `addRule()` and `append()` methods, you can also use parameter binding. This is again useful for dynamic rules where variables are needed to be assigned. Let's start by writing some rules first and assign some parameters to them.

```php
protected $rules = array(
    'username' => 'required|alpha|between:{min},{max}',
    'email' => 'required|unique:{table}',
    'birthday' => 'before:{date}'
);
```

As easy as it gets! The names of the parameters aren't restricted in any way, as long as they're within curly braces and unique, otherwise they'll get overwitten by preceeding rules. Now that you've got that cleared, let's bind those parameters to some real values.

There are 3 ways to bind parameters and we'll explore them in the following example:

```php
$userValidator = UserValidator::make(Input::all());

// One by one
$userValidator->bind('min', 5);
$userValidator->bind('max', 15);
$userValidator->bind('table', 'users')
$userValidator->bind('date', '2012-12-12');

// As an array
$userValidator->bind([
    'min' => 5,
    'max' => 15,
    'table' => 'users',
    'date' => '2012-12-12'
]);

// Overloading
$userValidator->bindMin(5);
$userValidator->bindMax(15);
$userValidator->bindTable('users');
$userValidator->bindDate('2012-12-12');
```

Each of the methods gets the same results, so use what you're more confortable with.

## Before and After Methods

There are two methods that you can add to your validation classes and get them called by ValidatorAssistant. The `before` method will be run when the validation class has been initialized, just after the inputs and rules have been set. The `after` method will be run after validation has finished, as the last thing ValidatorAssistant does. There's no limitation to the code you can write inside them, but it would obviously make no sense if they contain some arbitrary code. They're better used for doing manipulations on rules, adding messages on certain conditions, etc.

It's quite easy to add them:

```php
class UserValidator extends ValidatorAssistant {

    protected $rules = array(/* some rules */);

    protected function before()
    {
        // Rules, inputs, filters, attributes and
        // messages can be manipulated.
        $this->rules['username'] = 'required|alpha';
        $this->inputs['username'] = strtoupper($this->inputs['username']);
        $this->filters['username'] = 'trim';
        $this->attributes['username'] = 'Your name';
        $this->messages['username.required'] = "Username can't be empty.";
    }

    protected function after($validator)
    {
        if ($validator->fails()) {
            // run some code
        }
    }

}
```

As you can see, the `before` method is a good place for some manipulation logic or conditions. While the `after` method, which gets the validator instance as an argument, can be used for running code depending on the status of the validation.

## Custom Rules

Laravel supports custom rules via the `extend()` method of Validator. To make the process as easy as possible, custom rules can be created as methods inside validator classes. Those methods just need a "custom" prefix followed by the name of the custom rule and behave exactly as the closures described in Laravel's docs.

```php
class UserValidator extends ValidatorAssistant {

    protected $rules = array(
        'username' => 'required|foo',
        'email' => 'foo_bar'
    );

    protected function customFoo($attribute, $value, $parameters)
    {
        return $value == 'foo';
    }

    protected function customFooBar($attribute, $value, $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

}
```

The only convention is that method names should begin with a "custom" prefix and the rule name in camelCase. For example, "my_rule" should be written as "customMyRule()".

## Integrating Fadion/Rule

[Rule](https://github.com/fadion/Rule) is another package of mine that allows expressive building of validation rules and messages, using methods instead of arrays. Go check it out!

Integrating Rule with ValidatorAssistant is very easy using the `before()` method, as rules and messages can be build before actually running the validator.

```php
class UserValidator extends ValidatorAssistant {

    protected function before()
    {
        Rule::add('username')->required()->alpha();
        Rule::add('email')->required()->email();

        // Rules with custom messages
        Rule::add('password')
            ->required()->message("Password is required.")
            ->between(5, 15)->message("Password must be between 5 to 15 characters.");

        $this->rules = Rule::get();
        $this->messages = RuleMessage::getMessages();
    }

}
```

Scoped rules can be built with `Rule` too:

```php
protected function before()
{
    Rule::add('username')->required()->alpha();
    Rule::add('email')->required()->email();

    $this->rules = Rule::get();

    Rule::add('username')->required();
    Rule::add('email')->email();

    // Add a 'profile' scope
    $this->rulesProfile = Rule::get();
}
```

And finally, bindings:

```php
protected function before()
{
    Rule::add('age')->min('{min}');
    Rule::add('date')->date('{date}');

    $this->rules = Rule::get();
}
```
