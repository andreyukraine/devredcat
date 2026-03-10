<?php
// Лишаємо namespace DB, щоб клас лежав поряд із драйверами
namespace DB;

class MySQLiLogger {
  /** @var \DB */
  private $db;

  private $log = [];
  private $count = 0;
  private $timeTotal = 0.0;

  // ВАЖЛИВО: приймаємо глобальний \DB (а не DB\MySQLi)
  public function __construct(\DB $db) {
    $this->db = $db;
  }

  public function query($sql) {
    $t0 = microtime(true);
    $result = $this->db->query($sql);
    $dt = microtime(true) - $t0;

    $this->count++;
    $this->timeTotal += $dt;
    $this->log[] = [
      'sql' => $sql,
      'time_ms' => round($dt * 1000, 2),
    ];

    return $result;
  }

  // Проксі всі інші методи (escape, countAffected, getLastId, тощо)
  public function __call($name, $args) {
    return $this->db->$name(...$args);
  }

  public function __get($name) {
    return $this->db->$name;
  }

  public function dumpToFile() {
    $lines = [];
    $lines[] = 'TOTAL: ' . $this->count . ' queries, ' . round($this->timeTotal * 1000, 2) . " ms\n";
    foreach ($this->log as $i => $row) {
      $lines[] = sprintf("#%03d [%sms] %s", $i + 1, $row['time_ms'], $row['sql']);
    }
    $file = DIR_STORAGE . 'logs/sql-' . date('Ymd-His') . '-' . uniqid() . '.log';
    file_put_contents($file, implode("\n", $lines));
  }

  public function getStats() {
    return [
      'count' => $this->count,
      'time_ms' => round($this->timeTotal * 1000, 2),
      'queries' => $this->log
    ];
  }
}
