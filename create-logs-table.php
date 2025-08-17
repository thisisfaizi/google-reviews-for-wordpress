<?php
/**
 * Manual script to create the missing logs table
 * Run this once to fix the database error
 */

// Load WordPress
require_once('../../../wp-load.php');

global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

// Create logs table
$table_name = $wpdb->prefix . 'gmrw_logs';
$sql = "CREATE TABLE $table_name (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    timestamp datetime DEFAULT CURRENT_TIMESTAMP,
    level varchar(20) NOT NULL DEFAULT 'error',
    message text NOT NULL,
    context longtext,
    error_type varchar(50),
    PRIMARY KEY (id),
    KEY timestamp (timestamp),
    KEY level (level),
    KEY error_type (error_type)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

echo "Logs table created successfully!\n";
echo "Table name: $table_name\n";

// Test if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
echo "Table exists: " . ($table_exists ? 'Yes' : 'No') . "\n";
