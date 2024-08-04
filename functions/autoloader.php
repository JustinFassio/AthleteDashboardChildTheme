<?php
// functions/autoloader.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Autoloader {
    public static function register() {
        spl_autoload_register(array(new self, 'autoload'));
    }

    public function autoload($class) {
        // Convert class name to file name
        $file_name = strtolower(str_replace('_', '-', $class));
        
        // Define possible file locations
        $possible_locations = array(
            get_stylesheet_directory() . '/functions/' . $file_name . '.php',
            get_stylesheet_directory() . '/classes/' . $file_name . '.php'
        );

        // Check each possible location
        foreach ($possible_locations as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
}

Autoloader::register();