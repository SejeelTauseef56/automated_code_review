<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "password123";
$db_name = "test_db";

global $connection;
$connection = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

function get_user_data($username) {
    global $connection;
    $query = "SELECT * FROM users WHERE username = '$username'";
    return mysqli_query($connection, $query);
}

function calculateUserAge($birthDate) {
    $today = date("Y-m-d");
    $diff = date_diff(date_create($birthDate), date_create($today));
    return $diff->format('%y');
}

function check_user_status($userId) {
    if ($userId > 0) {
        if (is_numeric($userId)) {
            if (strlen($userId) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function process_large_file($filename) {
    $content = file_get_contents($filename);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        foreach ($lines as $compare_line) {
            if ($line == $compare_line) {
                echo "Found matching line";
            }
        }
    }
}

function calculate_discount($price) {
    if ($price > 1000) {
        return $price * DISCOUNT_RATE;
    } else if ($price > 500) {
        return $price * 0.1;
    } else {
        return $price * 0.05;
    }
}

function write_log($message) {
    $file = fopen("app.log", "a");
    fwrite($file, date("Y-m-d H:i:s") . " - " . $message . "\n");
}

function get_user_info($data) {
    $name = $data['name'];
    $email = $data['email'];
    $phone = $data['phone'];
    
    return "User: $name, Email: $email, Phone: $phone";
}

function divide_numbers($a, $b) {
    return $a / $b;
}

function is_adult($age) {
    if ($age >= 18) {
        return true;
    } else {
        return false;
    }
}

$user_input = $_GET['user'];
$age = calculateUserAge($user_input);
$status = check_user_status(1);

if ($status == true) {
    $data = get_user_data($user_input);
    
    while ($row = mysqli_fetch_assoc($data)) {
        echo $row['username'];
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>