<?php

namespace linkphp\db;

use PDO;
use Closure;
use PDOException;
use linkphp\interfaces\DatabaseInterface;
use PDOStatement;

class Query implements DatabaseInterface
{

    /**
     * 保存连接
     */
    private $connect;

    /**
     * 数据库配置文件
     * @array $database
     */
    private $database = [];

    /**
     * @var PDO
     */
    private $_pdo;

    /**
     * @var Builder
     */
    private $_build;

    /**
     * 表名
     * @var $table
     */
    private $table;

    private $distinct;

    /**
     * 字段
     * @var $field
     */
    private $field = '*';

    /**
     * 条件
     * @var $where
     */
    private $where;

    /**
     * 关联
     * @var $join
     */
    private $join;

    /**
     * 限制条数
     * @var $limit
     */
    private $limit;

    /**
     * 排序
     * @var $order
     */
    private $order;

    /**
     * 分组
     * @var $group
     */
    private $group;

    /**
     * 条件
     * @var $having
     */
    private $having;

    /**
     * 关联
     * @var $union
     */
    private $union;

    private $lock;

    private $value;

    /**
     * 执行错误
     * @var $error
     */
    private $error;

    /**
     * @var PDOResult
     */
    private $pdo_result;

    /**
     * @var update语句
     */
    private $update;

    public function __construct(
        Connect $connect,
        PDOResult $PDOResult)
    {
        $this->_pdo = $connect;
        $this->pdo_result = $PDOResult;
    }

    /**
     * 数据库连接方法
     * @return PDO;
     */
    public function connect()
    {
        if(isset($this->connect)){
            return $this->connect;
        }
        $class = "linkphp\\db\\connect\\" . ucfirst($this->database[0]['db_type']);
        $this->connect = (new $class())
            ->setConfig($this->database)
            ->connect();
        return $this->connect;
    }

    /**
     * @return PDOStatement;
     */
    private function pdo()
    {
        return $this->pdo_result->result;
    }

    public function import($file)
    {
        if(is_array($file)) $this->database = $file;
        return;
    }

    private function PDOStatement($pdo = '')
    {
        return $this->_pdo->pdoStatement($pdo);
    }

    public function prepare($sql, $bind=null)
    {
        $this->PDOStatement($this->connect()->prepare($sql));
        if(isset($bind)){
            foreach ($bind as $k => $v){
                $this->bindValue($k+1,$v,PDO::PARAM_INT);
            }
        }
        return ;
    }

    public function bindParam($parameter, $variable, $data_type, $length)
    {
        return $this->PDOStatement()
            ->bindParam(
            $parameter,
            $variable,
            $data_type,
            $length
        );
    }

    public function bindValue($parameter, $value, $data_type)
    {
        return $this->PDOStatement()
            ->bindValue(
            $parameter,
            $value,
            $data_type
        );
    }

    /**
     * 数据库查询语句解析方法
     * 返回对应所有相关二维数组
     * @param array|null $data
     * @return Query
     */
    public function select($data=null)
    {
        if(!is_null($data)){
            if (count($data) == count($data, 1)) {
                $this->pdoStatement($this->prepare($data[0]));
            } else {
                $this->pdoStatement($this->prepare($data[0], $data[1]));
            }
        } else {
            $this->query($this->build()->select($this));
            return $this->get();
        }
        return $this->pdoResult();
    }

    /**
     * 数据库查询语句解析方法
     * 返回对应一条相关数组
     * @param array|null $data
     * @return PDOResult
     */
    public function find($data=null)
    {
        if(!is_null($data)){
            $this->pdoStatement($this->prepare($data));
        } else {
            $this->query($this->build()->select($this));
            return $this->getOne();
        }
        return $this->pdoResult();
    }

    public function insert(array $data)
    {
        $field = '';
        $value = '';
        foreach($data as $k => $v){
            if(is_numeric($k)){
                $this->pdoStatement($this->prepare($data));
                return $this->pdoResult();
            }
            $field .= $k . ',';
            $value .=  "'" . $v . "'" . ',';
        }
        $field = substr($field, 0, -1);
        $value = substr($value, 0, -1);
        $this->field($field);
        $this->value($value);
        return $this->exec($this->build()->insert($this));
    }

    public function insertAll(array $data)
    {
        foreach($data as $k => $v){
            $values = '(';
            foreach ($v as $id => $item) {
                $values .= $item . ',';
            }
            $value[] = substr($values, 0, -1) . ')';
        }
        $value = implode(',',$value);
        $field = implode(',',array_keys($data[0]));
        $this->value($value);
        $this->field($field);
        return $this->exec($this->build()->insertAll($this));
    }

    public function delete($data=null)
    {
        if(!is_null($data)){
            $this->pdoStatement($this->prepare($data));
        } else {
            return $this->exec($this->build()->delete($this));
        }
        return $this->pdoResult();
    }

    public function update($data)
    {
        if (count($data) == count($data, 1)) {
            if(is_array($data)){
                array_walk_recursive($data,[$this,'parserUpdate']);
                return $this->exec($this->build()->update($this));
            } else {
                $this->pdoStatement($this->prepare($data));
            }
            return $this->pdoResult();
        } else {
            foreach ($data as $k => $v){
                if($v[0] == 'INC'){
                    $condition = [$k => $k . '+' . $v[1]];
                    array_walk_recursive($condition,[$this,'parserUpdate'], true);
                } elseif($v[0] == 'DEC') {
                    $condition = [$k => $k . '-' . $v[1]];
                    array_walk_recursive($condition,[$this,'parserUpdate'], true);
                }
            }
            return $this->exec($this->build()->update($this));
        }
    }

    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * 解析update语句
     * @param $value
     * @param $key
     * @param boolean $set 是否设置字段增加获减少值
     */
    private function parserUpdate($value, $key, $set = false)
    {
        if($set){
            $this->update .= $key . " = $value,";
            return;
        }
        $this->update .= $key . " = '$value',";
    }

    public function count($filed)
    {
        $this->field("COUNT($filed)");
        $this->exec($this->build()->select($this));
        return $this->getOne();
    }

    public function sum($filed)
    {
        $this->field("SUM($filed)");
        $this->query($this->build()->select($this));
        return $this->getOne();
    }

    /**
     * 字段值()增长
     * @param $field
     * @param $step
     * @return integer|true
     */
    public function setInc($field, $step=1)
    {
        return $this->setField($field, ['INC', $step]);
    }

    /**
     * 字段值()减少
     * @param $field
     * @param $step
     * @return integer|true
     */
    public function setDec($field, $step=1)
    {
        return $this->setField($field, ['DEC', $step]);
    }

    /**
     * 设置记录的某个字段值
     * 支持使用数据库字段和方法
     * @access public
     * @param  string|array $field 字段名
     * @param  mixed        $value 字段值
     * @return integer
     */
    public function setField($field, $value = '')
    {
        if (is_array($field)) {
            $data = $field;
        } else {
            $data[$field] = $value;
        }
        return $this->update($data);
    }

    /**
     * 执行一条预处理语句
     * @throws PDOException
     * @return bool
     */
    public function execute()
    {
        if($result = $this->PDOStatement()->execute()){
            return $result;
        }
        $this->error($this->PDOStatement()->errorInfo());
        throw new PDOException($this->error['errorInfo']);
    }

    public function pdoResult()
    {
        $this->execute();
        $this->pdo_result->result = $this->PDOStatement();
        return $this;
    }

    public function table($table)
    {
        if(is_array($table)){
            $this->table = $table[0];
            return $this;
        }
        $this->table = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function distinct($distinct)
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function getDistinct()
    {
        return $this->distinct;
    }

    public function field($field)
    {
        $this->field = $field;
        return $this;
    }

    public function getField()
    {
        return $this->field;
    }

    public function value($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function where($condition)
    {
        $this->where = ' WHERE ' . $condition;
        return $this;
    }

    public function getWhere()
    {
        return $this->where;
    }

    public function join($join)
    {
        $this->join[] = $join;
        return $this;
    }

    public function leftJoin($join)
    {
        $this->join[] = ' LEFT JOIN ' . $join;
        return $this;
    }

    public function rightJoin($join)
    {
        $this->join[] = ' RIGHT JOIN ' . $join;
        return $this;
    }

    public function getJoin()
    {
        $this->parserJoin();
        return $this->join;
    }

    private function parserJoin()
    {
        if(isset($this->join)){
            $sql = '';
            foreach ($this->join as $k => $v){
                $sql .= $v . ' ';
            }
            $this->join = $sql;
        }
    }

    public function limit($limit)
    {
        $this->limit = ' LIMIT ' . $limit;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function group($group)
    {
        $this->group = ' GROUP BY ' . $group;
        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function having($having)
    {
        $this->having = ' HAVING ' . $having;
        return $this;
    }

    public function getHaving()
    {
        return $this->having;
    }

    public function union($union)
    {
        $this->union = ' UNION Join ' . $union;
        return $this;
    }

    public function getUnion()
    {
        return $this->union;
    }

    public function lock($lock)
    {
        $this->lock = $lock;
        return $this;
    }

    public function getLocks()
    {
        return $this->lock;
    }

    public function getLastSql()
    {
        return $this->pdo_result->result->queryString;
    }

    public function transaction(){}

    public function beginTransaction()
    {
        $this->connect()->beginTransaction();
    }

    public function commit()
    {
        $this->connect()->commit();
    }

    public function rollback()
    {
        $this->connect()->rollBack();
    }

    public function insertGetId(array $data){}

    public function query($sql)
    {
        if($result = $this->connect()->query($sql)){
            $this->pdo_result->result = $result;
            $this->emptyAll();
            return $this->pdo_result;
        }
        $this->error($this->connect()->errorInfo());
        throw new PDOException($this->error['errorInfo']);

    }

    public function quote($string, $parameter_type = '')
    {
        $this->connect()->quote($string, $parameter_type);
    }

    public function exec($sql)
    {
        if($result = $this->connect()->exec($sql)){
            $this->pdo_result->rowNum = $result;
            $this->emptyAll();
            return $this->pdo_result->rowNum;
        }
        $this->error($this->connect()->errorInfo());
        throw new PDOException($this->error['errorInfo']);
    }

    /**
     * sql构造方法
     * @return Builder
     */
    private function build()
    {
        if(isset($this->_build)){
            return $this->_build;
        }
        $class = "linkphp\\db\\build\\" . ucfirst($this->database[0]['db_type']);
        $this->_build = new $class();
        return $this->_build;
    }

    public function getOne()
    {
        return $this->pdo()->fetch();
    }

    public function get()
    {
        return $this->pdo()->fetchAll();
    }

    private function error($error_info)
    {
        $this->error['errorCode'] = $error_info[0];
        $this->error['errorInfo'] = $error_info[2];
    }

    public function getError()
    {
        return $this->error;
    }

    private function emptyAll()
    {
        $this->table = '';
        $this->where = '';
        $this->field = '*';
        $this->limit = '';
        $this->distinct = '';
        $this->group = '';
        $this->value = '';
        $this->union = '';
        $this->order = '';
        $this->lock = '';
        $this->join = '';
        $this->having = '';
    }

}
