<?php

class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Private constructor to prevent direct instantiation
    }

    private function __clone()
    {
        // Private clone method to prevent cloning
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Define the absolute path to the project root
            $rootPath = realpath(__DIR__ . '/..');
            $dbPath = $rootPath . '/data/organon.db';
            
            try {
                self::$instance = new PDO('sqlite:' . $dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->exec('PRAGMA foreign_keys = ON;');
            } catch (PDOException $e) {
                // For a real app, you would log this error, not die.
                // For this POC, dying is simple and clear.
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
