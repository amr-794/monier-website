<?php
// Set the content type to JSON with UTF-8 encoding
header('Content-Type: application/json; charset=utf-8');

// Include the necessary functions file which also handles database connection
require_once '../includes/functions.php';

try {
    // Get the PDO database connection object
    $pdo = get_db_connection();
    
    // SQL query to fetch only active locations
    // We order by ID as created_at is not present in the final table design
    $sql = "SELECT id, name, address, latitude, longitude, working_hours, phone 
            FROM locations 
            WHERE is_active = 1 
            ORDER BY id ASC";
    
    // Prepare and execute the statement
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Fetch all results into an associative array
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Encode the results as a JSON string and output it
    // JSON_UNESCAPED_UNICODE ensures Arabic characters are rendered correctly
    echo json_encode($locations, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (PDOException $e) {
    // If a database error occurs, log it for the developer
    error_log("API Error (get_locations): " . $e->getMessage());
    
    // Send a generic error response to the client
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An error occurred while fetching location data.']);
}

?>