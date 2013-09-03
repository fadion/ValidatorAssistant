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

    // Error messages. When using Laravel's
    // default error messages, remove this property
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

When creating rules as an array, you can define as much scopes as you like. Look at the following example:

```php
protected $rules = array(
    'default' => array(
        'username' => 'required',
        'email' => 'required|email'
    ),
    'profile' => array(
        'name' => 'required',
        'age' => 'required|numeric|min:13'
    )
);
```

Now we'll initialize the validation class:

```php
// Validates the 'default' and 'profile' rules combined,
// with the 'profile' ruleset taking precedence
$userValidator = new UserValidator(Input::all(), 'profile');

// Validates only the 'default' rules
$userValidator = new UserValidator(Input::all(), 'default');

// Omitting the "scope" is the same as calling 'default'
$userValidator = new UserValidator(Input::all());
```

Consider the "default" scope as a shared ruleset that will be combined with any other scope you call. You can even define rules without a "default" scope if your validation sections are that different. In such a case, every scope will be a different ruleset and won't be combined with any other rule.

## Dynamics Rules and Messages

In addition to the defined rules and messages, you can easily add dynamic ones when the need rises. This is a convenient functionality, but should be used rarely because it defeats the purpose. In addition, they trigger a rerun of Laravel's Validator, so there's a small performance penalty too. Said that, let's quickly see how they work.

```php
$userValidator = new UserValidator(Input::all());

// New rules or messages will be added or overwrite existing
// ones. Rules are defined for the current "scope" only.
$userValidator->setRule('age', 'required|numeric|min:13');
$userValidator->setMessage('age.min', "Grow up mate!");
```

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
