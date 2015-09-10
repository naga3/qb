<?php
class QbTest extends PHPUnit_Framework_TestCase {
  private $db_name = 'qb_test';

  // 接続系テスト
  public function testConnect() {
    // 準備
    // MySQLデータベースの作成
    $db = new PDO('mysql:', 'root');
    $db->exec("CREATE DATABASE IF NOT EXISTS $this->db_name");

    // 接続・切断
    Qb::connect("sqlite:$this->db_name.db");
    Qb::close();
    $this->assertEquals(null, Qb::db());

    // オプションチェック
    Qb::connect("sqlite:$this->db_name.db");
    $this->assertEquals('id', Qb::config('primary_key'));
    $this->assertEquals(PDO::ERRMODE_EXCEPTION, Qb::config('error_mode'));
    $this->assertEquals(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT, Qb::config('json_options'));
    Qb::close();

    Qb::connect("sqlite:$this->db_name.db", [
      'primary_key' => 'id2',
      'error_mode' => PDO::ERRMODE_SILENT,
      'json_options' => JSON_HEX_TAG,
    ]);
    $this->assertEquals('id2', Qb::config('primary_key'));
    $this->assertEquals(PDO::ERRMODE_SILENT, Qb::config('error_mode'));
    $this->assertEquals(JSON_HEX_TAG, Qb::config('json_options'));
    Qb::close();

    Qb::connect("mysql:dbname=$this->db_name", 'root', '', [
      'primary_key' => 'id2',
      'error_mode' => PDO::ERRMODE_SILENT,
      'json_options' => JSON_HEX_TAG,
    ]);
    $this->assertEquals('id2', Qb::config('primary_key'));
    $this->assertEquals(PDO::ERRMODE_SILENT, Qb::config('error_mode'));
    $this->assertEquals(JSON_HEX_TAG, Qb::config('json_options'));
    Qb::close();

    Qb::connect("mysql:dbname=$this->db_name", 'root', [
      'primary_key' => 'id2',
      'error_mode' => PDO::ERRMODE_SILENT,
      'json_options' => JSON_HEX_TAG,
    ]);
    $this->assertEquals('id2', Qb::config('primary_key'));
    $this->assertEquals(PDO::ERRMODE_SILENT, Qb::config('error_mode'));
    $this->assertEquals(JSON_HEX_TAG, Qb::config('json_options'));
    Qb::close();

    // 設定テスト
    Qb::config('c1', 1);
    $this->assertEquals(1, Qb::config('c1'));
  }

  // CRUDテスト
  public function testCrud() {
    // テーブル作成
    Qb::connect("sqlite:$this->db_name.db");
    Qb::db()->exec('DROP TABLE IF EXISTS test');
    Qb::db()->exec('CREATE TABLE test(id INTEGER PRIMARY KEY, no INTEGER, name VARCHAR(255))');

    // INSERT
    $id = Qb('test')->save(['no' => 100, 'name' => '太郎']);
    $this->assertEquals(1, $id);
    $r = Qb('test')->where('no', 100)->oneArray();
    $this->assertEquals(['id' => 1, 'no' => 100, 'name' => '太郎'], $r);
    $r = Qb('test')->where('id', 1)->oneArray();
    $this->assertEquals(['id' => 1, 'no' => 100, 'name' => '太郎'], $r);
    $r = Qb('test')->where(1)->oneArray();
    $this->assertEquals(['id' => 1, 'no' => 100, 'name' => '太郎'], $r);
    $r = Qb('test')->oneArray('no', 100);
    $this->assertEquals(['id' => 1, 'no' => 100, 'name' => '太郎'], $r);
    $r = Qb('test')->oneArray(1);
    $this->assertEquals(['id' => 1, 'no' => 100, 'name' => '太郎'], $r);

    // UPDATE
    $id = Qb('test')->save(['no' => 200, 'name' => '二郎']);
    $this->assertEquals(2, $id);
    Qb('test')->where($id)->save(['no' => 300, 'name' => '三郎']);
    $r = Qb('test')->oneArray($id);
    $this->assertEquals(['id' => $id, 'no' => 300, 'name' => '三郎'], $r);
    Qb('test')->where(3)->save(['no' => 400, 'name' => '四郎']);
    $r = Qb('test')->oneArray(3);
    $this->assertEquals(['id' => 3, 'no' => 400, 'name' => '四郎'], $r);
    Qb('test')->where(4)->update(['no' => 500, 'name' => '五郎']);
    $r = Qb('test')->oneArray(4);
    $this->assertEmpty($r);

    // DELETE
    Qb('test')->where(1)->delete();
    $r = Qb('test')->oneArray(1);
    $this->assertEmpty($r);
    Qb('test')->delete(2);
    $r = Qb('test')->oneArray(2);
    $this->assertEmpty($r);
    Qb('test')->delete();
    $r = Qb('test')->oneArray(3);
    $this->assertEmpty($r);
  }
}
