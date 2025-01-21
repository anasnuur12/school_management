<?php

namespace App\Database;

use mysqli;

class Connection
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        // Xiriirka database-ka
        $this->conn = new mysqli(
            "localhost", // server-ka
            "root",     // username-ka
            "",         // password-ka
            "school_managementt" // magaca database-ka
        );

        // Hubi in xiriirku sax yahay
        if ($this->conn->connect_error) {
            die("Xiriirku ma shaqeynayo: " . $this->conn->connect_error);
        }
    }

    // Hel instance-ka kaliya ee class-kan
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Hel xiriirka database-ka
    public function getConnection()
    {
        return $this->conn;
    }
}