<?php
/**
 * sql基类。mysql和pgsql继承此类即可。
 * 书写规范检查：phpcs --standard=PSR2 src/DB/SQL/Maker/Base.php
 */

namespace DB\SQL\Maker;

abstract class Base
{
    protected $char = array();
    protected $conf;
    protected $db;

    public function __construct($dbConnection, array $conf = array())
    {
        if (!isset($conf['tableName']) || empty($conf['tableName'])) {
            throw new Exception('need conf: tableName');
        }
        $this->conf = $conf;
        $this->db = $dbConnection;
    }

    abstract protected function limit($input);

    abstract protected function quote($data);

    abstract protected function quoteColumn($data);

    abstract protected function quoteIdentifier($data);

    abstract protected function quoteTableName();
    
    /**
     * column array to string, 用于select和insert
     *
     * @param mixed $data
     *
     * @return   string
     */
    protected function implodeToColumn($data)
    {
        if (empty($data)) {
            throw new Exception();
        }
        if ($data == '*') {
            return '*';
        }
        if (is_array($data)) {
            if (empty($data)) {
                throw new Exception();
            }
            $tmp = array();
            foreach ($data as $one) {
                if (strpos($one, '(') !== false) {
                    $tmp[] = $one; //eg. point(location)
                } else {
                    $tmp[] = $this->quoteColumn($one);
                }
            }
            $r = implode(',', $tmp); //eg. `id`,`name`,`desc`
        } else {
            if (strpos($data, '(') === false) {
                $r = $this->quoteColumn($data); //eg. 'name'
            } else { //eg. sum(total)
                $r = $data;
            }
        }
        return $r;
    }

    protected function implodeToRowValues($data)
    {
        if (is_array($data)) {
            $tmp = array();
            foreach ($data as $one) {
                $tmp[] = $this->quote($one);
            }
            $a = implode(',', $tmp);
        } else {
            $a = $this->quote($data);
        }
        $b = '(' . $a . ')';
        return $b;
    }

    /**
     * input: array(
     *  array(1, 'jim'),
     *  array(2, 'lucy'),
     * )
     * output: ('1','jim'),('2','lucy')
     * @param array $data
     * @return string
     */
    protected function implodeToRowsValues($data)
    {
        foreach ($data as $value) {
            $tmp = $this->implodeToRowValues($value);
            $row_array[] = $tmp;
        }
        $rows = implode(',', $row_array);
        return $rows;
    }

    /**
     * input: array(
     *      'id' => array(
     *          1,2,3
     *      ),
     *      'year' => '2012'
     * )
     * output: WHERE `id` IN ('1','2','3') AND `year`='2012'
     * @param array $data
     * @return string
     */
    protected function implodeToWhere($data)
    {
        if (!is_array($data) || empty($data)) {
            return '';
        }
        $r = '';
        $map = array(
            'e' => '=',
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
            'ne' => '!=',
        );
        foreach ($map as $str => $math) {
            if (!isset($data[$str]) || empty($data[$str])) {
                continue;
            }
            foreach ($data[$str] as $key => $value) {
                if (is_array($value)) {
                    $tmp = array();
                    foreach ($value as $one) {
                        $tmp[] = $this->quote($one);
                    }
                    $part_1 =  $this->quoteIdentifier($key) . ' IN (' . implode(',', $tmp) . ') ';
                } elseif (is_null($value)) {
                    $part_1 = $this->quoteIdentifier($key) . ' IS NULL ';
                } else {
                    $part_1 = $this->quoteIdentifier($key) . $math . $this->quote($value);
                }
                if (!empty($r)) {
                    $r .= ' AND ';
                }
                $r .= $part_1;
            }
        }
        if (empty($r)) {
            return '';
        } else {
            return ' WHERE ' . $r;
        }
    }

    /**
     * input: array(
     *      'name' => 'jim',
     *      'year' => '2012'
     * )
     * output: `name`='jim',`year`='2012'
     * @param array $data
     * @return string
     */
    protected function implodeToUpdate($data)
    {
        if (empty($data)) {
            throw new Exception();
        }
        $r = '';
        /*if(isset($data['add'])) {
            foreach($data['add'] as $input['columns']=>$value) {
                if (!empty($r)) {
                    $r .= ',';
                }
                $r .= $this->quoteIdentifier($input['columns']) . '=' . $this->quoteIdentifier($input['columns']) . '+' . $this->quote($value);
            }
        }
        unset($data['add']);*/
        foreach ($data as $column => $value) {
            if (!empty($r)) {
                $r .= ',';
            }
            $r .= $this->quoteIdentifier($column) . '=' . $this->quote($value);
        }
        return $r;
    }

    public function delete(
        $input = array(
            'where' => '',
            'limit'   => '',
        )
    ) {
        $sql = 'DELETE FROM ' . $this->quoteTableName() . $this->implodeToWhere($input['where']);
        if (isset($input['limit']) && !empty($input['limit'])) {
            $sql .= $this->limit($input);
        }
        return $sql;
    }
 
    public function deleteRow(
        $input = array(
            'where' => '',
        )
    ) {
        $input['limit'] = 1;
        return $this->delete($input);
    }
  
    public function deleteRows(
        $input = array(
            'where' => '',
            'limit' => '',
        )
    ) {
        if (!isset($input['limit']) || empty($input['limit'])) {
            throw new Exception('need params: limit');
        }
        return $this->delete($input);
    }
   
    public function insertRow($data)
    {
        return 'INSERT INTO ' . $this->quoteTableName() . ' (' . $this->implodeToColumn(array_keys($data)) . ') VALUES ' . $this->implodeToRowValues(array_values($data));
    }

    public function insertRows($data)
    {
        $newData = array();
        $input['columns'] = array_keys($data[0]);
        foreach ($data as $one_row) {
            $newData[] = array_values($one_row);
        }
        return 'INSERT INTO ' . $this->quoteTableName() . ' (' . $this->implodeToColumn(array_values($input['columns'])) . ') VALUES ' . $this->implodeToRowsValues($newData);
    }

    public function select(
        $input = array(
            'where'   => '',
            'columns' => '*',
            'offset'  => 0,
            'limit'   => '',
            'groupBy' => '',
            'orderBy' => '',
        )
    ) {
        if (!isset($input['columns'])) {
            $input['columns'] = '*';
        }
        if (!isset($input['where'])) {
            $input['where'] = null;
        }
        $sql = 'SELECT ' . $this->implodeToColumn($input['columns']) . ' FROM ' . $this->quoteTableName() . $this->implodeToWhere($input['where']);
        if (isset($input['groupBy']) && !empty($input['groupBy'])) {
            $sql .= ' GROUP BY ' . $this->implodeToColumn($input['groupBy']);
        }
        if (isset($input['orderBy']) && !empty($input['orderBy'])) {
            if (strpos(trim($input['orderBy']), ' ') !== false) {
                $tmp = explode(' ', trim($input['orderBy']));
                $sql .= ' ORDER BY ' . $this->quoteColumn($tmp[0]) . ' ' . strtoupper($tmp[1]);
            } else {
                $sql .= ' ORDER BY ' . $this->quoteColumn($input['orderBy']);
            }
        }
        if (isset($input['limit']) && !empty($input['limit'])) {
            if (!isset($input['offset'])) {
                $input['offset'] = 0;
            }
            $sql .= $this->limit($input);
        }
        return $sql;
    }

    public function selectCount($input = array('where' => null, 'column' => '*'))
    {
        if (!isset($input['column'])) {
            $input['column'] = '*';
        }
        if ($input['column'] != '*') {
            $sql = 'SELECT COUNT(' . $this->quoteColumn() . ') as cnt';
        } else {
            $sql = 'SELECT COUNT(*) as cnt';
        }
        $sql .= ' FROM ' . $this->quoteTableName();
        if (isset($input['where']) && !empty($input['where'])) {
            $sql .= $this->implodeToWhere($input['where']);
        }
        if (isset($input['groupBy']) && !empty($input['groupBy'])) {
            $sql .= ' GROUP BY ' . $this->implodeToColumn($input['groupBy']);
        }
        return $sql;
    }
    
    public function update(
        $input = array(
            'data' => array(),
            'where' => null
        )
    ) {
        $sql = 'UPDATE ' . $this->quoteTableName() . ' SET ' . $this->implodeToUpdate($input['data']) . $this->implodeToWhere($input['where']);
        if (isset($input['limit']) && !empty($input['limit'])) {
            $sql .= $this->limit($input);
        }
        return $sql;
    }
 
    public function updateRow(
        $input = array(
            'data' => array(),
            'where' => null
        )
    ) {
        $input['limit'] = 1;
        return $this->update($input);
    }
  
    public function updateRows(
        $input = array(
            'data' => array(),
            'where' => '',
            'limit' => '',
        )
    ) {
        if (!isset($input['limit']) || empty($input['limit'])) {
            throw new Exception('need params: limit');
        }
        return $this->update($input);
    }
}
