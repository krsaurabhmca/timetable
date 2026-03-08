<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "timetable_db";

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (mysqli_query($conn, $sql)) {
    mysqli_select_db($conn, $dbname);
}
else {
    die("Error creating database: " . mysqli_error($conn));
}

// Function to safely execute queries
function db_query($sql)
{
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        // Log error or handle it
        return false;
    }
    return $result;
}

// Function to escape strings
function db_escape($str)
{
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}

// Initialize tables if they don't exist (loading from schema.sql)
// For simplicity in this demo, we'll assume the user runs the SQL or we can run it here
// We'll check if a table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($table_check) == 0) {
    $schema_path = __DIR__ . '/sql/schema.sql';
    if (file_exists($schema_path)) {
        $schema = file_get_contents($schema_path);
        $queries = explode(';', $schema);
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query != '') {
                mysqli_query($conn, $query);
            }
        }
    }
}

define('BASE_URL', '/timetable');
?>
