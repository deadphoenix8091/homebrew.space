<?php

namespace HomebrewSpace;

class DatabaseManager {
    static $elasticaClient = null;

    private static function IsConnected() {
        return self::$elasticaClient !== null;
    }

    private static function Connect() {
        $database = ConfigManager::GetConfiguration('database.database');
        $host = ConfigManager::GetConfiguration('database.host');
        $username = ConfigManager::GetConfiguration('database.username');
        $password = ConfigManager::GetConfiguration('database.password');
        self::$elasticaClient = new \Elastica\Client(array(
            'servers' => array(
                array('host' => 'es01', 'port' => 9200),
                array('host' => 'es02', 'port' => 9200)
            )
        ));
    }

    
}