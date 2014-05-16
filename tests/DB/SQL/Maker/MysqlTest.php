<?php
require_once __DIR__ . '/../../../autoload.php';

class MysqlTest extends PHPUnit_Framework_TestCase
{
    private $c;
    private $conf = array(
        //mysql or pgsql
        'product' => 'mysql',
        //pdo mysqli or pgsql
        'api' => 'mysqli',
        //'unix_domain_socket = '/tmp/mysql.sock',
        'host' => '127.0.0.1',
        //mysql default 3306 pgsql default 5432
        'port' => 3306,
        'dbname' => 'test',
        'username' => 'root',
        'password' => '1',
        'charset' => 'utf8',
    );

    public function setUp()
    {
        if ($this->conf['api'] == 'pdo') {
            $dsn = 'mysql:';
            $dsn .= 'host=' . $this->conf['host'] . ';port=' . $this->conf['port'] . ';';
            $dsn .= 'dbname=' . $this->conf['dbname'];
            $conn = new \PDO($dsn, $this->conf['username'], $this->conf['password']);
            if (isset($this->conf['charset']) && !empty($this->conf['charset'])) {
                $conn->query('SET NAMES '. $this->conf['charset']);
            }
        } elseif ($this->conf['api'] == 'mysqli') {
            try {
                $conn = new \mysqli($this->conf['host'], $this->conf['username'], $this->conf['password'], $this->conf['dbname'], $this->conf['port']);
                if ($conn->connect_error) {
                    throw new \Exception($conn->connect_error, $conn->connect_errno);
                }
                $conn->set_charset($this->conf['charset']);
            } catch (\Exception $e) {
                throw new \Exception($conn->error);
            }
        }

        $this->c = new \DB\SQL\Maker\Mysql($conn, array(
            'tableName' => 'users',
        ));
    }

    public function testDelete()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'e' => array(
                    'id' => 1,
                )
            ),
        );
        $r = $this->c->delete($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testDeleteRow()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'e' => array(
                    'id' => 1,
                )
            ),
        );
        $r = $this->c->deleteRow($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testDeleteRows()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'e' => array(
                    'id' => array(1, 2),
                )
            ),
            'limit' => 2,
        );
        $r = $this->c->deleteRows($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testInsertRow()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'id' => 1,
            'name' => 'jim',
        );
        $r = $this->c->insertRow($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testInsertRows()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            array(
                'id' => 1,
                'name' => 'jim',
            ),
            array(
                'id' => 2,
                'name' => 'tom',
            ),
            array(
                'id' => 3,
                'name' => 'lucy\'s sister',
            )
        );
        $r = $this->c->insertRows($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }
    
    public function testSelect()
    {
        echo __FUNCTION__ . "\n";
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
        $r = $this->c->Select($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testSelectCount()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'lt' => array(
                    'id' => 3,
                )
            ),
            'groupBy' => 'sex',
        );
        $r = $this->c->SelectCount($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testUpdate()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'gte' => array(
                    'id' => 3,
                )
            ),
            'data' => array(
                'name' => 'jim'
            ),
        );
        $r = $this->c->update($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testUpdateRow()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'gte' => array(
                    'id' => 3,
                )
            ),
            'data' => array(
                'name' => 'jim'
            ),
        );
        $r = $this->c->updateRow($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }

    public function testUpdateRows()
    {
        echo __FUNCTION__ . "\n";
        $params = array(
            'where' => array(
                'gte' => array(
                    'id' => 3,
                )
            ),
            'data' => array(
                'name' => 'jim'
            ),
            'limit' => 2,
        );
        $r = $this->c->updateRows($params);
        var_dump($r);
        $this->assertEquals(true, isset($r[0]));
    }
}
