<?php
/**
 * Qb: Simple query builder
 *
 * @author Osamu Nagayama
 */

/**
 * Qb
 *
 * @since PHP 5.4
 */
class Qb {

  /** @var PDO PDOインスタンス */
  protected static $db = null;

  /** @var array 接続時のオプション */
  protected static $options = [];

  /** @var string 最後に実行したSQL文 */
  protected static $last_sql = '';

  /** @var string テーブル名 */
  protected $table = null;

  /** @var array SELECTカラム */
  protected $columns = [];

  /** @var array JOIN句 */
  protected $joins = [];

  /** @var array WHERE条件 */
  protected $conditions = [];

  /** @var array WHERE条件のバインド値 */
  protected $condition_binds = [];

  /** @var array 挿入・更新カラムと値のセット */
  protected $sets = [];

  /** @var array 挿入・更新バインド */
  protected $set_binds = [];

  /** @var string ORDER BY */
  protected $orders = [];

  /** @var string LIMIT */
  protected $limit = '';

  /** @var string OFFSET */
  protected $offset = '';

  /**
   * コンストラクタ
   *
   * newでオブジェクトを生成せず、関数Qbを使うこと。
   *
   * @param string $table テーブル名
   */
  public function __construct($table) {
    $this->table = $table;
  }

  /**
   * PDOインスタンスを取得する。
   *
   * @return PDO PDOインスタンス
   */
  public static function db() {
    return self::$db;
  }

  /**
   * 最後に実行したSQL文を取得する（デバッグ用）。
   *
   * @return string SQL文
   */
  public static function lastSql() {
    return self::$last_sql;
  }

  /**
   * データベースに接続する。
   *
   * Qb::connect($dsn); // ユーザー名とパスワードは空文字、オプションはデフォルト<br>
   * Qb::connect($dsn, $user); // パスワードは空文字、オプションはデフォルト<br>
   * Qb::connect($dsn, $user, $pass); // オプションはデフォルト<br>
   * Qb::connect($dsn, $user, $pass, $options);<br>
   * Qb::connect($dsn, $options);<br>
   * Qb::connect($dsn, $user, $options);<br>
   *
   * @param string $dsn 接続文字列
   * @param string $user ユーザー名
   * @param string $pass パスワード
   * @param array $options オプション<br>
   *  primary_key: プライマリキーのカラム名 default: 'id'<br>
   *  error_mode: エラーモード default: PDO::ERRMODE_EXCEPTION<br>
   *  json_options: JSONエンコード時のオプション default: JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT<br>
   */
  public static function connect($dsn, $user = '', $pass = '', $options = []) {
    if (is_array($user)) {
      $options = $user;
      $user = '';
      $pass = '';
    } else if (is_array($pass)) {
      $options = $pass;
      $pass = '';
    }
    self::$options = array_merge([
      'primary_key' => 'id',
      'error_mode' => PDO::ERRMODE_EXCEPTION,
      'json_options' => JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT,
    ], $options);
    self::$db = new PDO($dsn, $user, $pass);
    self::$db->setAttribute(PDO::ATTR_ERRMODE, self::$options['error_mode']);
  }

  /**
   * データベースから切断する。
   */
  public static function close() {
    self::$db = null;
  }

  /**
   * オプション値の設定・取得
   *
   * $valueをセットした場合は設定、省略した場合は取得する。
   *
   * @param string $name オプション名
   * @param mixed $value オプション値
   */
  public static function config($name, $value = null) {
    if ($value === null) {
      return self::$options[$name];
    } else {
      self::$options[$name] = $value;
    }
  }

  /**
   * SELECT
   *
   * カラムが複数のときは配列もしくはメソッドチェーンで複数回呼び出す。<br>
   * キーに別名を設定することが出来る。<br>
   * 省略時は全てのカラムが選択される。<br><br>
   *
   * 例：<br>
   * select('column1')->select('column2')<br>
   * select(['column1', 'column2', …])<br>
   * select(['alias1' => 'column1', 'column2', …])<br>
   *
   * @param string|array $columns カラム名
   *
   * @return Qb 自分自身のインスタンス
   */
  public function select($columns) {
    if (!is_array($columns)) $columns = array($columns);
    foreach ($columns as $alias => $column) {
      if (!is_numeric($alias)) $column .= " AS $alias";
      array_push($this->columns, $column);
    }
    return $this;
  }

  /**
   * INNER JOIN
   *
   * @param string $table JOINするテーブル名
   * @param string $condition 条件
   *
   * @return Qb 自分自身のインスタンス
   */
  public function join($table, $condition) {
    array_push($this->joins, "INNER JOIN $table ON $condition");
    return $this;
  }

  /**
   * LEFT OUTER JOIN
   *
   * @param string $table JOINするテーブル名
   * @param string $condition 条件
   *
   * @return Qb 自分自身のインスタンス
   */
  public function leftJoin($table, $condition) {
    array_push($this->joins, "LEFT JOIN $table ON $condition");
    return $this;
  }

  /**
   * WHERE =
   *
   * where(column, value)
   * where(value) カラムはidで固定（オプションで変更可）
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function where($column, $value = null) {
    if ($value === null) {
      $value = $column;
      $column = self::$options['primary_key'];
    }
    $this->_where($column, '=', $value);
    return $this;
  }

  /**
   * WHERE <>
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereNot($column, $value) {
    $this->_where($column, '<>', $value);
    return $this;
  }

  /**
   * WHERE >
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereGt($column, $value) {
    $this->_where($column, '>', $value);
    return $this;
  }

  /**
   * WHERE >=
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereGte($column, $value) {
    $this->_where($column, '>=', $value);
    return $this;
  }

  /**
   * WHERE <
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereLt($column, $value) {
    $this->_where($column, '<', $value);
    return $this;
  }

  /**
   * WHERE <=
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereLte($column, $value) {
    $this->_where($column, '<=', $value);
    return $this;
  }

  /**
   * WHERE LIKE
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereLike($column, $value) {
    $this->_where($column, 'LIKE', $value);
    return $this;
  }

  /**
   * WHERE NOT LIKE
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereNotLike($column, $value) {
    $this->_where($column, 'NOT LIKE', $value);
    return $this;
  }

  /**
   * WHERE IN
   *
   * @param string $column カラム名
   * @param array $values 値の配列
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereIn($column, $values) {
    $this->_where($column, 'IN', $values);
    return $this;
  }

  /**
   * WHERE NOT IN
   *
   * @param string $column カラム名
   * @param array $values 値の配列
   *
   * @return Qb 自分自身のインスタンス
   */
  public function whereNotIn($column, $values) {
    $this->_where($column, 'NOT IN', $values);
    return $this;
  }

  /**
   * WHERE 内部使用
   *
   * @param string $column カラム名
   * @param string $separator セパレーター（=, >, LIKE など）
   * @param mixed $value 値、または値の配列
   */
  protected function _where($column, $separator, $value) {
    if (is_array($value)) {
      $qs = '(' . implode(',', array_fill(0, count($value), '?')) . ')';
      array_push($this->conditions, "$column $separator $qs");
      foreach ($value as $v) {
        array_push($this->condition_binds, $v);
      }
    } else {
      array_push($this->conditions, "$column $separator ?");
      array_push($this->condition_binds, $value);
    }
  }

  /**
   * 挿入・更新値セット
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function set($column, $value = null) {
    if (is_array($column)) {
      $sets = $column;
    } else {
      $sets = [$column => $value];
    }
    $this->sets += $sets;
    return $this;
  }

  /**
   * UPDATE or INSERT
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return string プライマリキーの値
   */
  public function save($column = null, $value = null) {
    if ($column) $this->set($column, $value);
    $st = $this->_build();
    return self::$db->lastInsertId();
  }

  /**
   * UPDATE
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return string プライマリキーの値
   */
  public function update($column = null, $value = null) {
    if ($column) $this->set($column, $value);
    $st = $this->_build(['only_update' => true]);
    return self::$db->lastInsertId();
  }

  /**
   * ORDER BY ASC
   *
   * @param string $column カラム名
   *
   * @return Qb 自分自身のインスタンス
   */
  public function asc($column) {
    array_push($this->orders, "$column ASC");
    return $this;
  }

  /**
   * ORDER BY DESC
   *
   * @param string $column カラム名
   *
   * @return Qb 自分自身のインスタンス
   */
  public function desc($column) {
    array_push($this->orders, "$column DESC");
    return $this;
  }

  /**
   * LIMIT
   *
   * @param integer $num LIMIT値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function limit($num) {
    $this->limit = " LIMIT $num";
    return $this;
  }

  /**
   * OFFSET
   *
   * @param integer $num OFFSET値
   *
   * @return Qb 自分自身のインスタンス
   */
  public function offset($num) {
    $this->offset = " OFFSET $num";
    return $this;
  }

  /**
   * 配列で返す。
   *
   * @return array 複数のレコードデータ
   */
  public function toArray() {
    $st = $this->_build();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * オブジェクトで返す。
   *
   * @return object 複数のレコードデータ
   */
  public function toObject() {
    $st = $this->_build();
    return $st->fetchAll(PDO::FETCH_CLASS);
  }

  /**
   * JSONで返す。
   *
   * @return string 複数のレコードデータ
   */
  public function toJson() {
    $rows = $this->toArray();
    return json_encode($rows, self::$options['json_options']);
  }

  /**
   * 最初のレコードのみを配列で返す。
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return array 単一のレコードデータ
   */
  public function oneArray($column = null, $value = null) {
    if ($column !== null) {
      $this->where($column, $value);
    }
    $st = $this->_build();
    return $st->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * 最初のレコードのみをオブジェクトで返す。
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return object 単一のレコードデータ
   */
  public function oneObject($column = null, $value = null) {
    if ($column !== null) {
      $this->where($column, $value);
    }
    $st = $this->_build();
    return $st->fetch(PDO::FETCH_CLASS);
  }

  /**
   * 最初のレコードのみをJSONで返す。
   *
   * @param string $column カラム名
   * @param mixed $value 値
   *
   * @return string 単一のレコードデータ
   */
  public function oneJson($column = null, $value = null) {
    if ($column !== null) {
      $this->where($column, $value);
    }
    $row = $this->oneArray();
    return json_encode($row, self::$options['json_options']);
  }

  /**
   * レコード数を返す。
   *
   * @return integer レコード数
   */
  public function count() {
    $st = $this->_build(array('count' => true));
    return $st->fetchColumn();
  }

  /**
   * DELETE
   *
   * @param string $column カラム名
   * @param mixed $value 値
   */
  public function delete($column = null, $value = null) {
    if ($column !== null) {
      $this->where($column, $value);
    }
    $st = $this->_build(array('delete' => true));
  }

  /**
   * SQL組み立て 内部使用
   *
   * @param array $params オプション
   *
   * @return PDOStatement PDOStatement
   */
  protected function _build($params = []) {
    $sql = '';
    $sql_where = '';

    // 条件組み立て
    $conditions = implode(' AND ', $this->conditions);
    if ($conditions) {
      $sql_where .= " WHERE $conditions";
    }

    if ($this->sets) {
      $insert = true;
      // 条件がある場合はUPDATE
      if ($this->conditions) {
        $insert = false;
        $columns = implode('=?,', array_keys($this->sets)) . '=?';
        $this->set_binds = array_values($this->sets);
        $sql = "UPDATE $this->table SET $columns";
        $sql .= $sql_where;
        $st = $this->_query($sql);
        if ($st->rowCount() === 0 && empty($params['only_update'])) $insert = true;
      }
      // 条件がない場合、またはUPDATE出来なかったときはINSERT
      if ($insert) {
        $columns = implode(',', array_keys($this->sets));
        $this->set_binds = array_values($this->sets);
        $qs = implode(',', array_fill(0, count($this->sets), '?'));
        $sql = "INSERT INTO $this->table($columns) VALUES($qs)";
        $this->condition_binds = array();
        $st = $this->_query($sql);
      }
    } else {
      if (!empty($params['delete'])) {
        // DELETE
        $sql = "DELETE FROM $this->table";
        $sql .= $sql_where;
        $st = $this->_query($sql);
      } else {
        // SELECT
        $columns = implode(',', $this->columns);
        if (!$columns) $columns = '*';
        if (!empty($params['count'])) $columns = "COUNT($columns) AS count";
        $sql = "SELECT $columns FROM $this->table";
        $joins = implode(' ', $this->joins);
        if ($joins) {
          $sql .= " $joins";
        }
        $order = '';
        if (count($this->orders) > 0) $order = ' ORDER BY ' . implode(',', $this->orders);
        $sql .= $sql_where . $order . $this->limit . $this->offset;
        $st = $this->_query($sql);
      }
    }
    return $st;
  }

  /**
   * クエリ発行 内部使用
   *
   * @param string $sql SQL文
   *
   * @return PDOStatement PDOStatement
   */
  protected function _query($sql) {
    $binds = array_merge($this->set_binds, $this->condition_binds);
    $st = self::$db->prepare($sql);
    $st->execute($binds);
    self::$last_sql = $sql;
    return $st;
  }
}

/**
 * メソッドチェーンの起点となる関数。
 *
 * @param string $table テーブル名
 *
 * @return Qb Qbインスタンス
 */
function Qb($table) {
  return new Qb($table);
}
