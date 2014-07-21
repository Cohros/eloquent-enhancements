# Eloquent Enhancements
This package aims to provide extra functionalities to Laravel's Eloquent. The functionalities, for now, are provided in form of traits, so you don't have to change your structure.

## Error

```php
use Sigep\EloquentEnhancements\Traits\Error
```
This trait add two methods to your models.


### setErrors
```php
setErrors(Illuminate\Support\MessageBag $errors)
```
Receives a MessageBag and set in `$errors` property. The `SaveAll` uses it to store errors and allow you to use them in your controllers or views.

### errors
```php
errors()
```

Returns errors setted by `setErrors()`. Create a empty MessageBag if errors is not defined.


## SaveAll
```php
use Sigep\EloquentEnhancements\Traits\SaveAll
```

This tait add the ability to save related objects in just one call. For example, if you have a User model who is related to Phone model, in a hasMany relationship, you can save a user with many phones with just one method call.

Consider the following models:

```php
class User extends Eloquent
{
    use Sigep\EloquentEnhancements\Traits\Errors;
    use Sigep\EloquentEnhancements\Traits\SaveAll;
    
    public function phones()
    {
        return $this->hasMany('Phone');
    }
}

class Phone extends Eloquent
{
    public function user()
    {
        return $this->belongsTo('User');
    }
}

```
> We strongly suggest that you use model's observers to validate your data and use the `setErrors()` to transport validation messages.

You can create a user with two phones using the `createAll()` method.

```php
$input = array(
    'name' => 'Bob',
    'email' => 'bob@gmail.com',
    'phones' => array (
        array('number' => '1111111'),
        array('number' => '2222222'),
    ),
);

$bob = new User();
$bob->createAll($input);
```

Note that we have a key with the name of the relationship that we create on User model. This is necessary so SaveAll knows which model are involved and how save your data.
If everything is fine, `createAll` will return true. Else, will return false.

Now, if you need to edit a number using the User model (when you have a form that shows all data, for example), you can use the `saveAll()` method.

```php
$input = array(
    'name' => 'Bob',
    'email' => 'bob@gmail.com',
    'phones' => array (
        array('id' => 1, 'number' => '111-1111'),
        array('id' => 2, 'number' => '222-2222'),
    ),
);

$bob = User::find(1); // assuming bob have the id = 1
$bob->saveAll($input);
```

Now, see that we add the id from the number on the array. This is necessary so SaveAll knows that is not a new record, but an update.

You do something similar when you need to remove a related model. You just need to pass the `_delete` key:

```php
$input = array(
    'name' => 'Bob',
    'email' => 'bob@gmail.com',
    'phones' => array (
        array('id' => 1, '_delete' => true),
    ),
);

$bob = User::find(1); // assuming bob have the id = 1
$bob->saveAll($input);
```

In this case, the phone `#1`  will be removed. The other properties are not necessary, just the `id` and the `_delete` key.

`SaveAll` can handle BelongsToMany relationships to. You just have to use like the examples above. But that kind of relationship has one particularity. If the pivot table has more columns them just the foreign keys, you can create a Relationship Model to handle the validations (assuming that you are using model observers to do the validation like suggested before :) ).

> More examples soon.

--

> Written with [StackEdit](https://stackedit.io/).