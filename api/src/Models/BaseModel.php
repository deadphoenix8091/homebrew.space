<?php

namespace HomebrewSpace\Models;

class BaseModel {
    protected $_data;
    protected $_identifier;
    
    public function __construct($_data)
    {
        $this->_data = $_data;
    }

    public function __get($name) {
        if (isset($this->_data[$name])) return $this->_data[$name];

        return $name;
    }

    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function ToJSON() {
        return json_encode($this->_data);
    }

    public function Save() {
        //@TODO: Implement me lol
    }
}