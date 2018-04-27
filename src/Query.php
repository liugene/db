<?php

namespace linkphp\db;

use PDO;
use Closure;
use linkphp\interfaces\DatabaseInterface;

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

    public function __construct(
        Connect $connect,
        PDOResult $PDOResult,
        Builder $builder)
    {
        $this->_pdo = $connect;
        $this->pdo_result = $PDOResult;
        $this->_build = $builder;
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
        $this->connect = $this->_pdo
            ->setConfig($this->database)
            ->connect();
        return $this->connect;
    }

    public function pdo()
    {
        return $this->pdo_result->result;
    }

    public function import($file)
    {
        if(is_array($file)) $this->database = $file;
        return;
    }

    public function PDOStatement($pdo = '')
    {
        return $this->_pdo->pdoStatement($pdo);
    }

    public function prepare($sql)
    {
        return $this->connect()->prepare($sql);
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
     * @return PDOResult
     */
    public function select($data=null)
    {
        if(!is_null($data)){
            $this->pdoStatement($this->prepare($data));
        } else {
            $this->query($this->_build->select($this));
            return $this->fetchAll();
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
            $this->query($this->_build->select($this));
            return $this->fetch();
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
        return $this->exec($this->_build->insert($this));
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
        return $this->exec($this->_build->insertAll($this));
    }

    public function delete($data=null)
    {
        if(!is_null($data)){
            $this->pdoStatement($this->prepare($data));
        } else {
            return $this->exec($this->query($this->_build->delete($this)));
        }
        return $this->pdoResult();
    }

    public function update($data=null)
    {
        if(!is_null($data)){
            $this->pdoStatement($this->prepare($data));
        } else {
            return $this->exec($this->query($this->_build->update($this)));
        }
        return $this->pdoResult();
    }

    /**
     * 执行一条预处理语句
     * @return bool
     */
    public function execute()
    {
        return $this->PDOStatement()->execute();
    }

    public function pdoResult()
    {
        $this->execute();
        $this->pdo_result->result = $this->PDOStatement()->fetchAll();
        return $this->pdo_result;
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
        $this->join = $join;
        return $this;
    }

    public function getJoin()
    {
        return $this->join;
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
        $this->group = $group;
        return $this;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function having($having)
    {
        $this->having = $having;
        return $this;
    }

    public function getHaving()
    {
        return $this->having;
    }

    public function union($union)
    {
        $this->union = $union;
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

    public function count($filed){}

    public function sum($filed){}

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
        $this->pdo_result->result = $this->connect()->query($sql);
        $this->emptyAll();
        return $this->pdo_result;
    }

    public function quote($string, $parameter_type = '')
    {
        $this->connect()->quote($string, $parameter_type);
    }

    public function exec($sql)
    {
        $this->pdo_result->rowNum = $this->connect()->exec($sql);
        return $this->pdo_result->rowNum;
    }

    public function build(){}

    public function fetch()
    {
        return $this->pdo_result->result->fetch();
    }

    public function fetchAll()
    {
        return $this->pdo_result->result->fetchAll();
    }

    public function error()
    {
        $this->error['errorCode'] = $this->PDOStatement()->errorCode();
        $this->error['errorInfo'] = $this->PDOStatement()->errorInfo();
    }

    public function getError()
    {
        return $this->error;
    }

    public function emptyAll()
    {
        $this->table = '';
        $this->where = '';
        $this->field = '';
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
