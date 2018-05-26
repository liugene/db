<?php

namespace linkphp\db\connect;

use linkphp\db\Connect;

class Mysql extends Connect
{

    public function paramDns()
    {
        if($this->config['dns'] == ''){
            return 'mysql:host=' . $this->host() . ':' . $this->port() . ';dbname=' . $this->dbName();
        }
        return $this->config['dns'];
    }

}
