# Qb: very simple query builder

https://github.com/naga3/qb

API提供あたりを想定したシンプルなPDOクエリビルダです。
なるべく短く書けるのを念頭に作成しました。

doc/index.html にリファレンスがあります。

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
$json = Qb('contact')->where('status', 1)->toJson();
```

contactテーブルのstatusカラムが1のものを返却します。

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
$json = Qb('contact')->save(['name' => '鈴木一郎', 'age' => 19]);
```

contactテーブルにnameカラムが「鈴木一郎」、ageカラムが「19」でレコードを挿入します。

## INSERT or UPDATE

```php
$json = Qb('contact')->where('age' => 20)->save(['name' => '鈴木一郎', 'age' => 19]);
```

WHERE句がある場合はまずUPDATEを試みて、対象のレコードが無ければINSERTします。

## UPDATE

```php
$json = Qb('contact')->where('age' => 20)->update(['name' => '鈴木一郎', 'age' => 19]);
```

こちらは対象のレコードが無くてもINSERTされません。

## DELETE

```php
$json = Qb('contact')->where('age' => 20)->delete();
```

contactテーブルのageカラムが20のレコードを全て削除します。

# 最後に

プルリクまってます！
