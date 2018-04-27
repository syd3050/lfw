<?php
namespace core;

use core\exception\MySQLException;

class DB
{
    private $dsn;
    /**
     * @var \PDOStatement
     */
    private $sth;
    /**
     * @var \PDO
     */
    private $dbh;
    private $dbname;
    private $host;
    private $user;
    private $charset;
    private $password;

    public $lastSQL = '';

    private static $_type = [
        'mysql' => 'mysql:dbname=$dbname;host=$host',
    ];

    public function __construct($config = array())
    {
        $config = $this->_parse($config);
        $this->dbname = $config['dbname'];
        $this->host = $config['host'];

        $this->dsn = strtr(self::$_type[$config['type']], ['$dbname'=>$config['dbname'], '$host'=>$config['host']]);
        //die($this->dsn);
        $this->user = $config['username'];
        $this->password = $config['password'];
        $this->charset = $config['charset'];
        $this->connect();
    }

    private function _parse($config=[])
    {
        $_config = Config::get('database');
        $_config = array_merge($_config, $config);
        if(empty($_config))
            throw  new MySQLException('请在app\config.php中配置数据库连接信息');
        $options = [
            'host','dbname','type','username','password'
        ];
        foreach ($options as $option) {
            if(!isset($_config[$option]))
                throw  new MySQLException("缺少配置项$option");
        }
        if(!isset(self::$_type[$_config['type']]))
            throw  new MySQLException("缺少配置项type的值非法");
        return $_config;
    }

    private function connect()
    {
        if(!$this->dbh){
            $options = array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $this->charset,
            );
            $this->dbh = new \PDO($this->dsn, $this->user, $this->password, $options);
        }
    }

    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    public function inTransaction()
    {
        return $this->dbh->inTransaction();
    }

    public function rollBack()
    {
        return $this->dbh->rollBack();
    }

    public function commit()
    {
        return $this->dbh->commit();
    }

    function watchException($execute_state)
    {
        if(!$execute_state){
            throw new MySQLException("SQL: {$this->lastSQL}\n".$this->sth->errorInfo()[2], intval($this->sth->errorCode()));
        }
    }

    private function fetchAll($sql, $parameters=[])
    {
        $result = [];
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        while($result[] = $this->sth->fetch(\PDO::FETCH_ASSOC)){ }
        array_pop($result);
        return $result;
    }

    private function fetchColumnAll($sql, $parameters=[], $position=0)
    {
        $result = [];
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        while($result[] = $this->sth->fetch(\PDO::FETCH_COLUMN, $position)){ }
        array_pop($result);
        return $result;
    }

    public function queryAll($table,$columns='*',$conditions=[])
    {
        if(is_array($columns))
            $columns = implode(',',$columns);
        list($condition,$params)= $this->_conditionParse($conditions);
        $cond_str = "1=1 $condition";
        $sql = "SELECT $columns FROM $table WHERE $cond_str";
        $models = $this->fetchAll($sql,$params);
        $r = [];
        foreach ($models as $item) {
            $r[] = $item;
        }
        return $r;
    }

/*    public function exists($sql, $parameters=[])
    {
        $this->lastSQL = $sql;
        $data = $this->fetch($sql, $parameters);
        return !empty($data);
    }*/

    private function _query($sql, $parameters=[])
    {
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        return $this->sth->rowCount();
    }

    public function query($table,$parameters=[])
    {
        $sql = "SELECT * FROM $table where id = :id";
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        return $this->sth->fetch(\PDO::FETCH_ASSOC);
    }

/*    public function fetch($sql, $parameters=[], $type=\PDO::FETCH_ASSOC)
    {
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        return $this->sth->fetch($type);
    }*/

    public function fetchColumn($sql, $parameters=[], $position=0)
    {
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        return $this->sth->fetch(\PDO::FETCH_COLUMN, $position);
    }

    private function _conditionParse($conditions)
    {
        if(empty($conditions))
            return ['',''];
        $params = [];
        $cond_keys = array_keys($conditions);
        foreach ($cond_keys as $k => $cond_key) {
            list($opt,$v) = $conditions[$cond_key];
            $params[$cond_key] = $v;
            //构建查询条件数组
            $cond_keys[$k] = $cond_key." $opt :$cond_key";
        }
        //构建查询条件
        $condition = ' AND '.implode(' AND ',$cond_keys);
        return [$condition, $params];
    }

    public function delete($table,$conditions)
    {
        $table = $this->format_table_name($table);
        list($condition,$params) = $this->_conditionParse($conditions);
        $cond_str = "1=1 $condition";
        $sql = "DELETE FROM $table WHERE $cond_str";
        return $this->_query($sql, $params);
    }

    /**
     * 更新数据库信息
     * @param string $table     表名
     * @param array $parameters 新列新值
     * @param array $condition  条件
     * @return int
     */
    public function update($table, $parameters=[], $condition=[])
    {
        $table = $this->format_table_name($table);
        $sql = "UPDATE $table SET ";
        $fields = [];
        $pdo_parameters = [];
        foreach ( $parameters as $field=>$value){
            $fields[] = '`'.$field.'`=:field_'.$field;
            $pdo_parameters['field_'.$field] = $value;
        }
        $sql .= implode(',', $fields);
        $fields = [];
        $where = '';
        if(is_string($condition)) {
            $where = $condition;
        } else if(is_array($condition)) {
            foreach($condition as $field=>$value){
                $parameters[$field] = $value;
                $fields[] = '`'.$field.'`=:condition_'.$field;
                $pdo_parameters['condition_'.$field] = $value;
            }
            $where = implode(' AND ', $fields);
        }
        if(!empty($where)) {
            $sql .= ' WHERE '.$where;
        }
        return $this->_query($sql, $pdo_parameters);
    }

    public function insert($table, $parameters=[])
    {
        $table = $this->format_table_name($table);
        $sql = "INSERT INTO $table";
        $fields = [];
        $placeholder = [];
        foreach ( $parameters as $field=>$value){
            $placeholder[] = ':'.$field;
            $fields[] = '`'.$field.'`';
        }
        $sql .= '('.implode(",", $fields).') VALUES ('.implode(",", $placeholder).')';

        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        $id = $this->dbh->lastInsertId();
        if(empty($id)) {
            return $this->sth->rowCount();
        } else {
            return $id;
        }
    }

    public function execute($sql,$parameters=[])
    {
        $this->lastSQL = $sql;
        $this->sth = $this->dbh->prepare($sql);
        $this->watchException($this->sth->execute($parameters));
        return $this->sth->rowCount();
    }

    public function errorInfo()
    {
        return $this->sth->errorInfo();
    }

    protected function format_table_name($table)
    {
        $parts = explode(".", $table, 2);

        if(count($parts) > 1) {
            $table = $parts[0].".`{$parts[1]}`";
        } else {
            $table = "`$table`";
        }
        return $table;
    }

    function errorCode()
    {
        return $this->sth->errorCode();
    }
}