<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "code_reviews";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all reviews
$sql = "SELECT id, file_name, feedback, created_at FROM reviews ORDER BY created_at DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<div class='review'>";
        echo "<h3>" . htmlspecialchars($row['file_name']) . "</h3>";
        echo "<p><strong>Review:</strong><br>" . nl2br(htmlspecialchars($row['feedback'])) . "</p>";
        echo "<small>Reviewed on: " . $row['created_at'] . "</small>";
        echo "</div><hr>";
    }
} else {
    echo "No reviews found.";
}

$conn->close();
?>
