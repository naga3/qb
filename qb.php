<?php
/**
 * クエリビルダ
 *
 * @since PHP 5.4
 */
class Qb {

  /** @var PDO PDOインスタンス */
  protected static $db = null;

  /**
   * @var array オプション
   */
  protected static $options = [];

  /** @var string テーブル名 */
  protected $table = null;

  /** @var array SELECTカラム */
  protected $columns = [];

  /** @var array JOIN */
  protected $joins = [];

  /** @var array WHERE条件 */
  protected $conditions = [];

  /** @var array WHERE条件条件バインド */
  protected $condition_binds = [];

  /** @var array 挿入・更新値 */
  protected $sets = [];

  /** @var array 挿入・更新バインド */
  protected $set_binds = [];

  /** @var string ORDER BY */
  protected $order = '';

  /** @var string LIMIT */
  protected $limit = '';

  /** @var string OFFSET */
  protected $offset = '';

  /**
   * PDOインスタンスを取得する。
   *
   * @return PDOインスタンス
   */
  public static function db() {
    return self::$db;
  }

  /**
   * データベースに接続する。
   *
   * Qb::connect(DSN);
   * Qb::connect(DSN, user);
   * Qb::connect(DSN, user, pass);
   * Qb::connect(DSN, user, pass, options);
   * Qb::connect(DSN, options);
   * Qb::connect(DSN, user, options);
   *
   * @param string $dsn 接続文字列
   * @param string $user ユーザー名
   * @param string $pass パスワード
   * @param array $options オプション
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
   * 条件セット
   */
  public function set($column, $value = null) {
    if (is_array($column)) {
      $sets = $column;
    } else {
      $sets = array($column => $value);
    }
    $this->sets += $sets;
    return $this;
  }

  /**
   * ORDER BY ASC
   */
  public function asc($column) {
    $this->order = " ORDER BY $column ASC";
    return $this;
  }

  /**
   * ORDER BY DESC
   */
  public function desc($column) {
    $this->order = " ORDER BY $column DESC";
    return $this;
  }

  /**
   * LIMIT
   */
  public function limit($num) {
    $this->limit = " LIMIT $num";
    return $this;
  }

  /**
   * OFFSET
   */
  public function offset($num) {
    $this->offset = " OFFSET $num";
    return $this;
  }

  /**
   * UPDATE or INSERT
   */
  public function save($column = null, $value = null) {
    if ($column) $this->set($column, $value);
    $st = $this->_build();
  }

  /**
   * UPDATE
   */
  public function update($column = null, $value = null) {
    if ($column) $this->set($column, $value);
    $st = $this->_build(array('only_update' => true));
  }

  /**
   * 配列で返す。
   */
  public function toArray() {
    $st = $this->_build();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * オブジェクトで返す。
   */
  public function toObject() {
    $st = $this->_build();
    return $st->fetchAll(PDO::FETCH_CLASS);
  }

  /**
   * JSONで返す。
   */
  public function toJson() {
    $rows = $this->toArray();
    return json_encode($rows, self::$options['json_options']);
  }

  /**
   * 最初のレコードのみを配列で返す。
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
   */
  public function count() {
    $st = $this->_build(array('count' => true));
    return $st->fetchColumn();
  }

  /**
   * DELETE
   */
  public function delete($column = null, $value = null) {
    if ($column !== null) {
      $this->where($column, $value);
    }
    $st = $this->_build(array('delete' => true));
  }

  /**
   * SQL組み立て 内部使用
   */
  protected function _build($params = array()) {
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
        $sql .= $sql_where . $this->order . $this->limit . $this->offset;
        $st = $this->_query($sql);
      }
    }
    return $st;
  }

  /**
   * クエリ発行 内部使用
   */
  protected function _query($sql) {
    $binds = array_merge($this->set_binds, $this->condition_binds);
    $st = self::$db->prepare($sql);
    $st->execute($binds);
    return $st;
  }
}

/**
 * メソッドチェーンの起点となる関数。
 *
 * @param string $table テーブル名
 * @return Qb Qbインスタンス
 */
function Qb($table) {
  return new Qb($table);
}
