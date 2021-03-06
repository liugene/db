<?php

namespace linkphp\db;

use PDO;
use PDOException;
use framework\interfaces\DatabaseInterface;
use PDOStatement;

class Query implements DatabaseInterface
{

    /**
     * 保存连接
     */
    private $connect;

    /**
     * @var Connect
     */
    private $_connect;

    /**
     * 数据库配置文件
     * @array $database
     */
    private $database = [];

    /**
     * @var Builder
     */
    private $_build;

    /**
     * 表前缀
     * @var $prefix
     */
    private $prefix;

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
     * @var string update语句
     */
    private $update;

    /**
     * @var array 事件
     */
    private $event;

    // 查询结果类型
    protected $fetchType = PDO::FETCH_ASSOC;
    // 字段属性大小写
    protected $attrCase = PDO::CASE_LOWER;
    // 返回或者影响记录数
    protected $numRows = 0;

    public function __construct(PDOResult $PDOResult)
    {
        $this->pdo_result = $PDOResult;
    }

    /**
     * 数据库连接方法
     * @return PDO;
     */
    public function connect()
    {
        if($this->connect){
            return $this->connect;
        }
        $class = "linkphp\\db\\connect\\" . ucfirst($this->database['db_type']);
        /**
         * @var Connect
         */
        $this->_connect = (new $class($this->database));
        $this->connect = $this->_connect->connect();

        // 数据返回类型
        if (isset($this->database['result_type'])) {
            $this->fetchType = $this->database['result_type'];
        }
        return $this->connect;
    }

    /**
     * @return Connect;
     */
    public function getQueryObject()
    {
        return $this->_connect;
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
        return $this;
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '')
    {
        return $config ? $this->database[$config] : $this->database;
    }

    private function PDOStatement($pdo = '')
    {
        return $this->_connect->pdoStatement($pdo);
    }

    public function prepare($sql, $bind=null)
    {
        $this->PDOStatement($this->connect()->prepare($sql));
        if($bind){
            foreach ($bind as $k => $v){
                $this->bindValue($k+1,$v,PDO::PARAM_INT);
            }
        }
        return $this;
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
            if (count($data) == count($data, 1)) {
                $this->pdoStatement($this->prepare($data[0]));
            } else {
                $this->pdoStatement($this->prepare($data[0], $data[1]));
            }
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

        if(is_numeric($value)){
            $this->update .= $key . " = $value,";
        }

        if(is_string($value)){
            $this->update .= $key . " = '$value',";
        }
    }

    public function count($filed)
    {
        $this->field("COUNT($filed)");
        $this->query($this->build()->select($this));
        $count = $this->getOne();
        return (int)$count[0];
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
        if($this->prefix){
            $prefix = $this->prefix;
        } else {
            $prefix = $this->database['dbprefix'];
        }
        $this->table = $prefix . $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function prefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
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
        $this->where = $condition;
        return $this;
    }

    public function getWhere()
    {
        $this->parserWhere();

        return $this->where;
    }

    private function parserWhere()
    {
        $where = $this->where;

        if(is_array($where)){
            $sql = '';
            foreach ($where as $k => $v){

                if(is_numeric($v)){
                    $sql .= " $k = $v" . ' AND ';
                }

                if(is_string($v)){
                    $sql .= " $k = \' $v \'" . ' AND ';
                }
            }

            $where = substr($sql, 0, strlen($sql)-4);
        }

        $this->where = $this->where ? ' WHERE ' . $where : '';
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
        if($this->join){
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
        $this->order = ' ORDER BY ' . $order;
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

    /**
     * 设置
     * @param $union
     * @return $this
     */
    public function union($union)
    {
        $this->union = ' UNION Join ' . $union;
        return $this;
    }

    /**
     * 获取
     * @return string
     */
    public function getUnion()
    {
        return $this->union;
    }

    /**
     * 设置锁
     * @param $lock
     * @return $this
     */
    public function lock($lock)
    {
        $this->lock = $lock;
        return $this;
    }

    /**
     * 获取锁
     * @return string
     */
    public function getLocks()
    {
        return $this->lock;
    }

    /**
     * 获取最后一个sql执行语句
     */
    public function getLastSql()
    {
        return $this->pdo_result->result->queryString;
    }

    /**
     * 执行数据库事务
     * @access public
     * @param callable $callback 数据操作方法回调
     * @return mixed
     * @throws PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function transaction($callback)
    {
        $this->beginTransaction();
        try {
            $result = null;
            if (is_callable($callback)) {
                $result = call_user_func_array($callback, [$this]);
            }
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 开启一个事务
     */
    public function beginTransaction()
    {
        $this->connect()->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->connect()->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback()
    {
        $this->connect()->rollBack();
    }

    public function insertGetId(array $data){}

    /**
     * 执行一条sql语句
     * @param $sql
     * @throws PDOException
     * @return PDOResult
     */
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

    /**
     * 执行一条sql语句，返回受影响行数
     * @throws PDOException
     * @param $sql
     */
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
        if($this->_build){
            return $this->_build;
        }
        $class = "linkphp\\db\\build\\" . ucfirst($this->database['db_type']);
        $this->_build = new $class();
        return $this->_build;
    }

    /**
     * 获取一个数组结果集
     */
    public function getOne()
    {
        $data = $this->pdo()->fetch($this->fetchType);
        $this->numRows = count($data);
        return $data;
    }

    /**
     * PDO获取多个数组结果集
     */
    public function get()
    {
        $data = $this->pdo()->fetchAll($this->fetchType);
        $this->numRows = count($data);
        return $data;
    }

    public function getNumRows()
    {
        return $this->numRows;
    }

    /**
     * 设置错误信息
     */
    private function error($error_info)
    {
        $this->error['errorCode'] = $error_info[0];
        $this->error['errorInfo'] = $error_info[2];
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置SQL时间
     * @param $event
     * @param $callback
     */
    public function event($event, $callback)
    {
        $this->event[$event] = $callback;
    }

    /**
     * 触发事件
     * @access protected
     * @param string $event   事件名
     * @param mixed  $params  额外参数
     * @return bool
     */
    protected function trigger($event, $params = [])
    {
        $result = false;
        if (isset($this->event[$event])) {
            $callback = $this->event[$event];
            $result   = call_user_func_array($callback, [$params, $this]);
        }
        return $result;
    }

    /**
     * 清空sql构造语句
     */
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
