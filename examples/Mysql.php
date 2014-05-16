<?php
/*
 * 例子
 * 使用步骤：
 * 1、composer update
 * 2、php Mysql.php
 */
require_once __DIR__ . '/vendor/autoload.php';

$conf = array(
    //mysql or pgsql
    'product' => 'mysql',
    //pdo mysqli or pgsql
    'api' => 'pdo',
    //'unix_domain_socket = '/tmp/mysql.sock',
    'host' => '127.0.0.1',
    //mysql default 3306 pgsql default 5432
    'port' => 3306,
    'dbname' => 'test',
    'username' => 'root',
    'password' => '1',
    'charset' => 'utf8',
);

$dsn = 'mysql:';
$dsn .= 'host=' . $conf['host'] . ';port=' . $conf['port'] . ';';
$dsn .= 'dbname=' . $conf['dbname'];
$conn = new \PDO($dsn, $conf['username'], $conf['password']);
if (isset($conf['charset']) && !empty($conf['charset'])) {
    $conn->query('SET NAMES '. $conf['charset']);
}

$c = new \DB\SQL\Maker\Mysql($conn, array('tableName' => 'users'));

$params = array(
    'where' => array(
        'e' => array(
            'id' => array(1, 2),
        )
    ),
    'limit' => 2,
    'orderBy' => 'id ASC',
    'groupBy' => 'sex',
);
$r = $c->Select($params);
var_dump($r);
