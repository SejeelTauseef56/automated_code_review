<?php

require __DIR__ . '/vendor/autoload.php';
use GuzzleHttp\Client;

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
                    ['role' => 'system', 'content' => 'You are a helpful assistant for code review.'],
                    ['role' => 'user', 'content' => "Analyze the following code and provide feedback:\n\n" . $code],
                ],
                'max_tokens' => 200,
            ],
        ]);

        $body = $response->getBody();
        $data = json_decode($body);

        return $data->choices[0]->message->content ?? 'No feedback generated.';
    }
}

// Directly use the environment variable for OPENAI_API_KEY (set in GitHub Secrets)
$openAIKey = getenv('OPENAI_API_KEY');

$openAI = new OpenAIClient($openAIKey);

// Find all PHP files in the repository (including subdirectories)
$phpFiles = glob('**/*.php', GLOB_BRACE);

// Prepare a directory to store feedback files
$feedbackDir = __DIR__ . '/feedback/';
if (!is_dir($feedbackDir)) {
    mkdir($feedbackDir);
}

// Analyze each file and save feedback
foreach ($phpFiles as $file) {
    $code = file_get_contents($file);
    $feedback = $openAI->analyzeCode($code);

    $feedbackFile = $feedbackDir . basename($file) . '_feedback.txt';
    file_put_contents($feedbackFile, $feedback);

    echo "Feedback for $file saved to $feedbackFile\n";
}
