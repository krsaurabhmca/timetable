<?php
if ($_SERVER['HTTP_HOST'] == 'timegrid.offerplant.com') {
    // Live Server Config (Hostinger)
    $host = "localhost";
    $username = "u960515621_timegrid";
    $password = "@Time_2001";
    $dbname = "u960515621_timegrid";
    define('BASE_URL', '');
}
else {
    // Local Development Config
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "timetable_db";
    define('BASE_URL', '/timetable');
}

session_start();
$org_id = $_SESSION['org_id'] ?? 0;
$org_where = $org_id ? " WHERE org_id = '$org_id' " : "";
$org_and = $org_id ? " AND org_id = '$org_id' " : "";

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists (Only for Local)
if ($_SERVER['HTTP_HOST'] != 'timegrid.offerplant.com') {
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    mysqli_query($conn, $sql);
}

// Select the database
if (!mysqli_select_db($conn, $dbname)) {
    die("Database selection failed: " . mysqli_error($conn));
}

// Function to safely execute queries
function db_query($sql)
{
    global $conn;
    try {
        $result = mysqli_query($conn, $sql);
        return $result;
    }
    catch (mysqli_sql_exception $e) {
        return false;
    }
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

// BASE_URL is now defined in the environment detection block above.
?>
