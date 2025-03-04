<?php

function run_query($query, $message) {
    $host= "mysql";
    $username = "root";
    $password = "root";

    // Create connection
    $conn = new mysqli($host, $username, $password);
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Run query
    if ($conn->query($query) === TRUE) {
        print_r($message);
        print_r("\n");
    } else {
        print_r("Error: " . $conn->error);
        print_r("\n");
    }

    $conn->close();
}

function drop_db() {
    run_query("DROP DATABASE magento", "Dropping Magento 2 database...");
    run_query("CREATE DATABASE magento", "Clean Magento 2 database created.");
  }

function drop_carts() {
    run_query("DELETE FROM magento.quote;", "Deleting quote items - all carts are empty now!");
  }

function set_blank_theme() {
    run_query('INSERT INTO magento.core_config_data (scope, scope_id, path,VALUE ) VALUES ("stores", 1, "design/theme/theme_id", 1 );', "Setting blank theme - don't forget to flush and reindex!");
}

if(key_exists(1, $argv) && function_exists($argv[1])) {
    call_user_func($argv[1]);
} else {
    print_r("PHP function not found - check arguments when running dp.php!");
    print_r("\n");
}

