<?php
namespace HomebrewSpace;

class SessionManager
{
    public static function Start()
    {
        session_start();
    }

    public static function IsLoggedin()
    {
        if (isset($_SESSION['user'])) {
            return true;
        } else {
            return false;
        }
    }

    public static function Logout()
    {
        unset($_SESSION['user']);
    }

    public static function Login($username, $password)
    {
        if (self::IsLoggedin())
            return;
        $user = DatabaseManager::Execute('select * from `user` where username = "' . DatabaseManager::Escape($username) . '" and passwort = "' .
            md5($password).'"');

        if (!$user)
            return;

        $_SESSION['user'] = $user;
    }

    public static function Register($username, $password)
    {
        if (self::IsLoggedin())
            return;

        $exists = DatabaseManager::Execute('select * from `user` where username = "' . DatabaseManager::Escape($username) . '"');

        if ($exists !== null)
            return;

        DatabaseManager::Execute('insert into `user` (username, passwort, created_at) values ("' . DatabaseManager::Escape($username) . '","' .
            md5($password) . '", NOW())');

        self::Login($username, $password);
    }
}