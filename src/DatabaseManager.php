<?php

namespace HomebrewDB;

class DatabaseManager {
    /** @var \mysqli */
    static $connection = null;

    private static function IsConnected() {
        return self::$connection !== null;
    }

    private static function Connect() {
        $database = ConfigManager::GetConfiguration('database.database');
        $host = ConfigManager::GetConfiguration('database.host');
        $username = ConfigManager::GetConfiguration('database.username');
        $password = ConfigManager::GetConfiguration('database.password');
        self::$connection = new \PDO('mysql:dbname=' . $database . ';host=' . $host, $username, $password);

        //@TODO: Add database error handling
    }

    public static function Escape($input) {
        if (!self::IsConnected())
            self::Connect();

        return mysqli_real_escape_string(self::$connection, $input);
    }

    public static function Prepare($sql) {
        if (!self::IsConnected())
            self::Connect();

        $preparedStatement = self::$connection->prepare($sql);
        return $preparedStatement;
    }

    public static function ExecuteAssoc($sql) {
        if (!self::IsConnected())
            self::Connect();

        $results_array = array();
        $result = self::$connection->query($sql);
        while ($row = $result->fetch_assoc()) {
            $results_array[] = $row;
        }

        return $results_array;
    }
}