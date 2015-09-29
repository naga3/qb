# Qb: Simple query builder

[Japanese README](https://github.com/naga3/qb/blob/master/README.ja.md)

This was assumed per the provision of the back-end API, a simple PDO query builder.
I was created in mind is that the write as short as possible the code.

```php
$rows = Qb('contact')->toJson();
```

Just only this you can return a list of the contact table in JSON.

## Audiences

Though it is to use the raw PDO as it is troublesome, who full-fledged DB library feels overkill function.

## Resources

doc/index.html is a reference.

* GitHub https://github.com/naga3/qb
* Packagist https://packagist.org/packages/naga3/qb

## How to install

only require 'qb.php'.

If you use the Composer is,

```
composer require naga3/qb
```

Install Now you read in the autoload.

```php
require_once 'vendor/autoload.php';
```

## Samples

sample/contact.php is a sample of a simple contact list.
sample/todo.php is a sample of the ToDo list that combines AngularJS.
Both samples PDO_SQLITE module is I will work as it is if it is introduced.

# API

## Connection

```php
Qb::connect('sqlite:sample.db');
Qb::connect('mysql:host=localhost;dbname=sample', 'user', 'pass');
```

Connect to the specified DSN.

## SELECT

```php
$rows = Qb('contact')->toJson();
```

It will return a list of the contact table in JSON.

```php
$rows = Qb('contact')->select('name')->select('tel')->toArray();
```

It will return the name column and tel column of the table contact list in the array.

```php
$rows = Qb('contact')->select(['name', 't' => 'tel'])->toObject();
```

name column of the table contact list I returned unchanged. And tel column will return as an object with an alias t.

## WHERE

```php
$row = Qb('contact')->where('id', 1)->oneArray();
```

id column of contact table will return one line of 1.

```php
$row = Qb('contact')->where(1)->oneArray(); // In the case of id column, it can be omitted column specified
$row = Qb('contact')->oneArray('id', 1);
$row = Qb('contact')->oneArray(1);
```

There is also such shorthand.

```php
$rows = Qb('contact')->whereGte('status', 1)->whereLike('name', '%Yamada%')->toJson();
```

In status column of the contact table is 1 or more, it will return the ones that contain "Yamada" in the name.

## JOIN

```php
$rows = Qb('contact')->join('access', 'access.contact_id = contact.id')->toJson();
```

INNER JOIN. 'access.contact_id = contact.id' is binding conditions.

```php
$rows = Qb('contact')->leftJoin('access', 'access.contact_id = contact.id')->toJson();
```

LEFT OUTER JOIN.

## INSERT

```php
$id = Qb('contact')->save(['name' => 'Ichiro Suzuki', 'age' => 19]);
```

to contact table name column is "Ichiro Suzuki", insert the record age column is "19". The return value is the value of the primary key.

## INSERT or UPDATE

```php
Qb('contact')->where('age', 20)->save(['name' => 'Ichiro Suzuki', 'age' => 19]);
```

In an attempt to first UPDATE If there is a WHERE clause to INSERT if there is no record of the target.

## UPDATE

```php
Qb('contact')->where('age', 20)->update(['name' => 'Ichiro Suzuki', 'age' => 19]);
```

Here even if there is no record of the target will not be INSERT.

```php
Qb('contact')->where('age', 20)->update('name', 'Ichiro Suzuki');
```

1 column only change you can also be written as this.

## SET

```php
Qb('contact')->where('age', 20)->set('age', 19)->set('name', 'Ichiro Suzuki')->update();
```

INSERT and from connecting the chain set, you can UPDATE.

## DELETE

```php
Qb('contact')->where('age', 20)->delete();
```

age column of contact table will remove all 20 of the record.

```php
Qb('contact')->delete(1);
```

id column of contact table will delete a record.

## ORDER BY

```php
$rows = Qb('contact')->asc('created_at')->toJson();
```

It will return a list of the contact table in ascending order of created_at column.

```php
$rows = Qb('contact')->desc('created_at')->asc('id')->toJson();
```

It will return a list of the contact table in descending order of created_at column, in ascending order of id.

## OFFSET, LIMIT

```php
$rows = Qb('contact')->offset(10)->limit(5)->toJson();
```

You get 5 from 10 th in the list of contact table.

## PDO object acquisition

```php
$db = Qb('contact')->db();
```

You get the raw PDO object. Please, for example, when you put the transaction.

## Option when connecting

```php
$options = [
  // Primary key
  'primary_key' => 'id',
  // ERRMODE
  'error_mode' => PDO::ERRMODE_EXCEPTION,
  // json_encode options
  'json_options' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
];
Qb::connect($dsn, $user, $pass, $options);
```

# important point

* 1 Program 1 connection with the assumption, is not suitable for large-scale programs.
