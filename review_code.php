<?php

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

// Get changed files passed from GitHub Actions
$changedFiles = $argv;  // The files will be passed as arguments

class OpenAIClient
{
    private $client;
    private $apiKey;

    public function __construct($apiKey)
    {
        if (empty($apiKey)) {
            echo "API Key is missing.\n";
            exit(1);  // Stop execution if API key is missing
        }

        $this->apiKey = $apiKey;
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function analyzeCode($code)
    {
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

        $body = $response->getBody();
        $data = json_decode($body);

        return $data->choices[0]->message->content ?? 'No feedback generated.';
    }
}

// Initialize OpenAI client
$openAI = new OpenAIClient(getenv('OPENAI_API_KEY'));

// Prepare a directory to store feedback files
$feedbackDir = __DIR__ . '/feedback/';
if (!is_dir($feedbackDir)) {
    mkdir($feedbackDir);
}

// Process each changed file
foreach ($changedFiles as $file) {
    if (empty($file)) continue;  // Skip empty file arguments

    // Get the diff for the changed file
    $diff = shell_exec("git diff HEAD~1 HEAD -- $file");

    // Analyze the diff of the file and get feedback
    $feedback = $openAI->analyzeCode($diff);

    // Save the feedback to a file
    $feedbackFile = $feedbackDir . basename($file) . '_feedback.txt';
    file_put_contents($feedbackFile, $feedback);

    echo "Feedback for $file saved to $feedbackFile\n";
}
