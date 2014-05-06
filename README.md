# ValidatorAssistant

Keep Laravel Controllers thin and reuse code by organizing validation rules and messages into dedicated classes. ValidatorAssistant is a small library built to be extended by those classes, so they can easily use Laravel's Validation system and a few additional features like subrules, scopes and binding.

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

3. (Optional) Add a new alias: `'ValidatorAssistant' => 'Fadion\ValidatorAssistant\ValidatorAssistant'` to your `app/config/app.php` file, inside the `aliases` array.

## Usage

ValidatorAssistant can be extended by any PHP class that follows just a few simple rules. As a personal preference, I use an `app/validators` folder to store validation classes and have added it to the `classmap` option of composer.json for simple autoloading. Probably it's a good idea to namespace them too. 

A validation class in action is written below. Note the $rules and $messages properties. For them to be "seen" by ValidatorAssistant, their visibility needs to be `protected` or `public`, but not `private`.

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

if ($userValidator->fails())
{
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

## Dynamics Rules and Messages

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