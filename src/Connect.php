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
use PDOException;

class Connect
{

    private $config = [];

    protected $dns;

    protected $user = 'root';

    protected $dbname;

    protected $password = '';

    private $_pdo;

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function user()
    {
        return $this->config[0]['dbuser'];
    }

    public function password()
    {
        return $this->config[0]['dbpwd'];
    }

    public function port()
    {
        return $this->config[0]['port'];
    }

    public function paramDns()
    {
        return $this->config[0]['dns'];
    }

    public function connect()
    {
        $this->_pdo = new PDO($this->paramDns(),$this->user(),$this->password());
        return $this->_pdo;
    }

    public function free(){}

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

}