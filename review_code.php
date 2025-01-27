<?php
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
                    'model'      => 'gpt-3.5-turbo',
                    'messages'   => [
                        ['role' => 'system', 'content' => 'You are a helpful assistant for code review. Please review the changes made to the code.'],
                        ['role' => 'user', 'content' => "Analyze the following changes and provide feedback:\n\n" . $code],
                    ],
                    'max_tokens' => 200,
                ],
            ]);

            $body = json_decode($response->getBody(), true);
            return $body['choices'][0]['message']['content'] ?? 'No feedback generated.';
        } catch (\Exception $e) {
            fwrite(STDERR, "Error during API call: " . $e->getMessage() . "\n");
            exit(1);
        }
    }
}

// Verify we have at least one file argument
if ($argc < 2) {
    fwrite(STDERR, "Usage: php review_code.php <file>\n");
    exit(1);
}

// Get the file to review from command line arguments
$file = $argv[1];

// Verify the file exists
if (!file_exists($file)) {
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
} catch (\Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}