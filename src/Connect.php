<?php

// +----------------------------------------------------------------------
// | LinkPHP [ Link All Thing ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016-2017 http://linkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liugene <liujun2199@vip.qq.com>
// +----------------------------------------------------------------------
// |               配置类
// +----------------------------------------------------------------------

namespace linkphp\db;

use PDO;
use PDOStatement;
use PDOException;

class Connect
{

    /**
     * @var PDOStatement
     */
    private $_PDOStatement;

    protected $config = [];

    protected $dns;

    protected $host;

    protected $user = 'root';

    protected $dbname;

    protected $password = '';

    private $_pdo;

    public function __construct($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 设置数据库的配置参数
     * @access public
     * @param string|array      $config 配置名称
     * @param mixed             $value 配置值
     * @return void
     */
    public function setConfig($config, $value = '')
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } else {
            $this->config[$config] = $value;
        }
    }

    /**
     * 获取数据库的配置参数
     * @access public
     * @param string $config 配置名称
     * @return mixed
     */
    public function getConfig($config = '')
    {
        return $config ? $this->config[$config] : $this->config;
    }

    public function host()
    {
        return $this->config['host'];
    }

    public function user()
    {
        return $this->config['dbuser'];
    }

    public function password()
    {
        return $this->config['dbpwd'];
    }

    public function port()
    {
        return $this->config['port'];
    }

    public function dbName()
    {
        return $this->config['dbname'];
    }

    public function paramDns()
    {
        return $this->config['dns'];
    }

    public function connect()
    {
        try {
            $this->_pdo = new PDO($this->paramDns(),$this->user(),$this->password());
            return $this->_pdo;
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    public function pdoStatement($pdo)
    {
        if(!empty($this->_PDOStatement)) return $this->_PDOStatement;
        $this->_PDOStatement = $pdo;
        return $this->_PDOStatement;
    }

    public function debug(){}

    public function free()
    {
        $this->_PDOStatement = null;
    }

    public function __destruct()
    {
        if($this->_PDOStatement){
            $this->free();
        }
        // TODO: Implement __destruct() method.
    }

}