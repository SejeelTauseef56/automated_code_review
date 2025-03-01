<?php
// Set up log file path
$logFile = __DIR__ . '/logs/error_log.txt';

// Ensure the logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true); // Create the directory with appropriate permissions
}

function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    error_log($logMessage, 3, $logFile);  // Log to the specified file
}

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

class OpenAIClient {
    private $client;
    private $apiKey;

    public function __construct($apiKey) {
        // Get API key from environment variable if not provided
        $this->apiKey = $apiKey ?: getenv('OPENAI_API_KEY');
        
        // Verify API key
        if (empty($this->apiKey)) {
            logError("Error: OpenAI API Key is missing");
            fwrite(STDERR, "Error: OpenAI API Key is missing\n");
            exit(1);
        }

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function analyzeCode($code) {
        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a thorough code reviewer. Analyze the code for security issues, best practices, performance problems, and maintainability concerns. Provide specific recommendations for improvements. Be detailed but concise.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Please review this code and provide detailed feedback on issues and improvements:\n\n" . $code
                        ]
                    ],
                    'max_tokens' => 1000,
                    'temperature' => 0.7,
                    'top_p' => 1,
                    'frequency_penalty' => 0,
                    'presence_penalty' => 0
                ],
            ]);

            $body = json_decode($response->getBody(), true);
            $feedback = $body['choices'][0]['message']['content'] ?? 'No feedback generated.';
            
            // Format the feedback for better readability
            $feedback = "Code Review Feedback\n" .
                       "===================\n\n" .
                       $feedback . "\n\n" .
                       "End of Review\n" .
                       "===================\n";
            
            return $feedback;
        } catch (\Exception $e) {
            logError("Error during API call: " . $e->getMessage());
            fwrite(STDERR, "Error during API call: " . $e->getMessage() . "\n");
            exit(1);
        }
    }
}

// Verify we have at least one file argument
if ($argc < 2) {
    logError("Usage: php review_code.php <file>");
    fwrite(STDERR, "Usage: php review_code.php <file>\n");
    exit(1);
}

// Get the file to review
$file = $argv[1];

// Verify the file exists
if (!file_exists($file)) {
    logError("Error: File '$file' does not exist");
    fwrite(STDERR, "Error: File '$file' does not exist\n");
    exit(1);
}

// Create feedback directory if it doesn't exist
$feedbackDir = __DIR__ . '/feedback/';
if (!is_dir($feedbackDir)) {
    mkdir($feedbackDir, 0755, true);
}

try {
    // Initialize OpenAI client
    $openAI = new OpenAIClient(getenv('OPENAI_API_KEY'));

    // Get the file content
    $content = file_get_contents($file);
    
    // Analyze the code and get feedback
    $feedback = $openAI->analyzeCode($content);

    // Save the feedback to a file
    $feedbackFile = $feedbackDir . basename($file) . '_feedback.txt';
    file_put_contents($feedbackFile, $feedback);

    // Output success
    echo $feedback . "\n";

    // Now, insert the feedback into the MySQL database
    try {
        $pdo = new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';port=' . getenv('DB_PORT'),
            getenv('DB_USER'),
            getenv('DB_PASS')
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        logError("Database connection failed: " . $e->getMessage());
        echo 'Connection failed: ' . $e->getMessage();
        exit(1);
    }
    
    $stmt = $pdo->prepare("INSERT INTO reviews (file_name, feedback) VALUES (:file_name, :feedback)");
    $stmt->bindParam(':file_name', basename($file));
    $stmt->bindParam(':feedback', $feedback);

    $stmt->execute();
    echo "Feedback successfully inserted into the database.\n";

} catch (\Exception $e) {
    logError("Error: " . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
?>
