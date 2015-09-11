# Qb: Simple query builder

バックエンドのAPI提供あたりを想定した、シンプルなPDOクエリビルダです。
なるべく短く書けるのを念頭に作成しました。

```php
$json = Qb('contact')->toJson();
```

これだけでcontactテーブルの一覧をJSONで返すことが出来ます。

## リソース

doc/index.html にリファレンスがあります。

* GitHub https://github.com/naga3/qb
* Packagist https://packagist.org/packages/naga3/qb

## インストール方法

qb.phpをrequireするだけでOKです。

Composerを使う場合は、

```
composer require naga3/qb
```

でインストールし、autoloadで読み込みます。

```php
require_once 'vendor/autoload.php';
```

## サンプル

sample/contact.php が簡単なコンタクトリストのサンプルです。
sample/todo.php がAngularJSを組み合わせたToDoリストのサンプルです。
どちらのサンプルもPDO_SQLITEモジュールが導入されていればそのまま動きます。

# API

## 接続

```php
Qb::connect('sqlite:sample.db');
Qb::connect('mysql:host=localhost;dbname=sample', 'user', 'pass');
```

指定したDSNに接続します。

## SELECT

```php
$json = Qb('contact')->toJson();
```

contactテーブルの一覧をJSONで返却します。

```php
$json = Qb('contact')->select('name')->select('tel')->toArray();
```

contactテーブル一覧のname, telカラムを配列で返却します。

```php
$json = Qb('contact')->select(['name', 't' => 'tel'])->toObject();
```

contactテーブル一覧のnameカラムをそのまま、telカラムは別名tでオブジェクトとして返却します。

## WHERE

```php
$json = Qb('contact')->where('id', 1)->oneArray();
```

contactテーブルのidカラムが1のものを一行返却します。

```php
$json = Qb('contact')->where(1)->oneArray(); // idカラムの場合、カラム指定を省略可能
$json = Qb('contact')->oneArray('id', 1);
$json = Qb('contact')->oneArray(1);
```

このような省略記法もあります。

```php
$json = Qb('contact')->whereGte('status', 1)->whereLike('name', '%山田%')->toJson();
```

contactテーブルのstatusカラムが1以上で、名前に「山田」が含まれているものを返却します。

## JOIN

```php
$json = Qb('contact')->join('access', 'access.contact_id = contact.id')->toJson();
```

INNER JOINです。access.contact_id = contact.id が結合条件です。

```php
$json = Qb('contact')->leftJoin('access', 'access.contact_id = contact.id')->toJson();
```

LEFT OUTER JOINです。

## INSERT

```php
$id = Qb('contact')->save(['name' => '鈴木一郎', 'age' => 19]);
```

contactテーブルにnameカラムが「鈴木一郎」、ageカラムが「19」でレコードを挿入します。戻り値はプライマリキーの値です。

## INSERT or UPDATE

```php
Qb('contact')->where('age', 20)->save(['name' => '鈴木一郎', 'age' => 19]);
```

WHERE句がある場合はまずUPDATEを試みて、対象のレコードが無ければINSERTします。

## UPDATE

```php
Qb('contact')->where('age', 20)->update(['name' => '鈴木一郎', 'age' => 19]);
```

こちらは対象のレコードが無くてもINSERTされません。

```php
Qb('contact')->where('age', 20)->update('name', '鈴木一郎');
```

1カラムのみの変更の場合はこのように書くことも出来ます。

## SET

```php
Qb('contact')->where('age', 20)->set('age', 19)->set('name', '鈴木一郎')->update();
```

setでチェーンを繋げてからINSERT, UPDATEが出来ます。

## DELETE

```php
Qb('contact')->where('age', 20)->delete();
```

contactテーブルのageカラムが20のレコードを全て削除します。

```php
Qb('contact')->delete(1);
```

contactテーブルのidカラムが1のレコードを削除します。

## ORDER BY

```php
$json = Qb('contact')->asc('created_at')->toJson();
```

contactテーブルの一覧をcreated_atカラムの昇順で返します。

```php
$json = Qb('contact')->desc('created_at')->toJson();
```

contactテーブルの一覧をcreated_atカラムの降順で返します。

## OFFSET, LIMIT

```php
$json = Qb('contact')->offset(10)->limit(5)->toJson();
```

contactテーブルの一覧の10件目から5件を取得します。

## PDOオブジェクト取得

```php
$db = Qb('contact')->db();
```

生のPDOオブジェクトを取得します。トランザクションを張る場合などにどうぞ。

## 接続時のオプション

```php
$options = [
  // プライマリキー
  'primary_key' => 'id',
  // ERRMODE
  'error_mode' => PDO::ERRMODE_EXCEPTION,
  // json_encode時のオプション
  'json_options' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
];
Qb::connect($dsn, $user, $pass, $options);
```

# 注意点

* 1プログラム1接続が前提で、大規模なプログラムには向いていません。
