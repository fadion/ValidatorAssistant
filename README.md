# ValidatorAssistant

Keep Laravel Controllers thin and reuse code by organizing validation rules and messages into simple classes. ValidatorAssistant is a small library built to be extended by those classes, so they can easily use Laravel's Validation system and a few additional features.

## Installation

1. Add the package to your composer.json file and run `composer update`:

```json
{
    "require": {
        "fadion/validator-assistant": "dev-master"
    }
}
```

2. Add `Fadion\ValidatorAssistant\ValidatorAssistantServiceProvider` to your `app/config/app.php` file, inside the `providers` array.

## Usage

ValidatorAssistant can be extended by any class and doesn't know or care of the way you organize your validation classes. As a personal preference, I use a `app/validators` folder to store validation classes and have added it to the `classmap` option of composer.json for simple autoloading.

A validation class:

```php
class UserValidator extends ValidatorAssistant
{

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

With the rules and messages in the validation class defined, a typical workflow in a controller would be as follows:

```php
// Fictional controller "UseController" and action "add"

$userValidator = new UserValidator(Input::all());

if ($userValidator->fails())
{
    return Redirect::back()->withInput()->withErrors($userValidator->instance());
}
```

Pretty neat, right?! Whenever you'll need to validate a model or form, just call the appropriate validation class and you'll be done with a few lines of code.

## Rules Scope

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
$userValidator = new UserValidator(Input::all(), 'profile');

// Validates the 'register' rules
$userValidator = new UserValidator(Input::all(), 'register');

// Omitting the scope will validate the 'default' rules
$userValidator = new UserValidator(Input::all());
```

## Dynamics Rules and Messages

In addition to the defined rules and messages, you can easily add dynamic ones when the need rises with the `setRule` and `setMessage` methods. This is a convenient functionality for those occassions when rules have to contain dynamic parameters (like the "unique" rule).

```php
$userValidator = new UserValidator(Input::all());

// New rules or messages will be added or overwrite existing
// ones. Rules are defined for the current "scope" only.
$userValidator->setRule('email', 'required|email|unique:users,email,10');
$userValidator->setMessage('email.unique', "Cmon!");
```

There's also the `appendRule` method that instead of rewritting a ruleset, will append a new rule to it. It works only on an existing input, but will fail silently. Additionally, it will overrive rules of the same type with the new ones. Considering the previous example and supossing that the "email" field has already a "required" rule, we can append to it as follows:

```php
// The combined rules will be: required|email|unique:users
$userValidator->appendRule('email', 'email|unique:users');
```

## Rules Parameter Binding

As a completely different and [probably] more elegant approach to the setRule() and appendRule() methods, but with basically the same purpose, you can also use parameter binding. This is again useful for dynamic rules where variables are needed to be assigned. Let's start by writing some rules first and assign some parameters to them.

```php
protected $rules = array(
    'username' => 'required|alpha|between:{min},{max}',
    'email' => 'required|unique:{table}',
    'birthday' => 'before:{date}'
);
```

As easy as it gets! The names of the parameters aren't restricted in any way, as long as they're within curly braces and unique, otherwise they'll get overwitten by preceeding rules. Now that you've got that cleared, let's bind those parameters to some real values.

There are 4 ways to bind parameters and we'll explore them in the following example:

```php
$userValidator = new UserValidator(Input::all());

// One by one
$userValidator->bind('min', 5);
$userValidator->bind('max', 15);
$userValidator->bind('table', 'users')
$userValidator->bind('date', '2012-12-12');

// As an array
$userValidator->bind(array(
    'min' => 5,
    'max' => 15,
    'table' => 'users',
    'date' => '2012-12-12'
));

// As pairs
$userValidator->bind('min', 5, 'max', 15, 'table', 'users', 'date', '2012-12-12');

// Overloading
$userValidator->bindMin(5);
$userValidator->bindMax(15);
$userValidator->bindTable('users');
$userValidator->bindDate('2012-12-12');
```

Each of the methods gets the same results, so use what you're more confortable with.

## More than Simple Arrays

We've seen how rules and messages can be defined inside a validation class. However, a class can do much more than that! You can create methods that do database work, create models, redirect and much more. It's up to you to decide how much logic will go to the validation classes and what makes sense.

Just as an example to give you the idea, using the UserValidator we talked about earlier:

```php
class UserValidator extends ValidatorAssistant
{

    protected $rules = array(
        'username' => 'required',
        'email' => 'required|email'
    );

    protected $messages = array(
        'username.required' => "Username is required",
        'email.required' => "Email is required",
        'email.email' => "Email is invalid"
    );

    public function redirectFailed()
    {
        if ($this->fails())
        {
            return Redirect::route('some/route')->withInput()->withErrors($this->instance());
        }

        return false;
    }

    public function saveUser()
    {
        if ($this->passes())
        {
            $user = User::create(array(
                'username' => $this->inputs['username'],
                'email' => $this->inputs['email']
            ));

            return $user;
        }
    }

}

// Later in a controller

$userValidator = new UserValidator(Input::all());

if ($userValidator->redirectFailed())
{
    return $userValidator->redirectFailed();
}

$userValidator->saveUser();
```
