<?php
/**
 * 生成mysql格式的sql语句
 * 书写规范检查：phpcs --standard=PSR2 src/DB/SQL/Maker/Mysql.php
 */

namespace DB\SQL\Maker;

final class Mysql extends Base
{
    protected $char = array(
        'identifierQuote' => '`',
        'valueQuote' => '\'',
    );

    /**
     * sql mode
     */
    private static $sqlMode = array(
        'ansi' => array(
            'identifierQuote' => '"',
            'valueQuote' => '\'',
            'end' => ';',
        ),
    );

    protected $conf = array(
        'api' => '', //pdo mysqli
        'tableName' => '',
    );

    public function __construct($dbConnection, array $conf = array())
    {
        parent::__construct($dbConnection, $conf);
        //mysql default sqlMode is empty
        if (isset($conf['sqlMode']) && !empty($conf['sqlMode'])) {
            if (isset($this->sqlMode[$conf['sqlMode']])) {
                $this->char = $this->sqlMode[$conf['sqlMode']];
            }
        }
        $className = \get_class($dbConnection);
        $this->conf['api'] = strtolower($className);
    }

    protected function limit(
        $input = array(
            'offset' => null,
            'limit'  => null,
        )
    ) {
        if (isset($input['limit'])) {
            if (isset($input['offset'])) {
                return ' LIMIT ' . intval($input['offset']) . ',' . intval($input['limit']);
            } else {
                return ' LIMIT ' . intval($input['limit']);
            }
        }
        return '';
    }

    protected function quote($data)
    {
        switch ($this->conf['api']) {
            case 'pdo':
                return $this->db->quote($data);
                break;
            case 'mysqli':
                return $this->char['valueQuote'] . $this->db->real_escape_string($data) . $this->char['valueQuote'];
                break;
            default:
                throw new Exception('unknown db api');
        }
    }

    protected function quoteColumn($data)
    {
        return $this->quoteIdentifier($data);
    }

    /**
     * @todo escape indentifier
     */
    protected function quoteIdentifier($data)
    {
        return $this->char['identifierQuote'] . $data . $this->char['identifierQuote'];
    }
    
    protected function quoteTableName()
    {
        return $this->quoteIdentifier($this->conf['tableName']);
    }
}
