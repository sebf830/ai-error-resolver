<?php
namespace App\Service;

use App\Entity\ErrorLog;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatGptResolver
{
    public function __construct(
        private HttpClientInterface $client, 
        private string $open_api_key,
        private LoggerInterface $logger
        ) {}

    public function resolveWithRetry(ErrorLog $errorLog, int $maxRetries = 2): ?string
    {
        $this->logger?->info("Received in GPT resolver");

        $attempt = 0;

        while ($attempt <= $maxRetries) {
            $prompt = "Voici une erreur provenant du service '{$errorLog->getServiceTYpe()}':\n\n".
                    $errorLog->getMessage() . "\n\n".
                    "Stacktrace:\n" . $errorLog->getStacktrace() . "\n\n".
                    "Contexte: " . json_encode($errorLog->getDatas()) .
                    "\n\nPropose la solution la plus précise.";

            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'timeout' => 10,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->open_api_key,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]
            ]);

            $data = $response->toArray();

            if($data['choices'][0]['message']['content']) return $data['choices'][0]['message']['content'];

            $attempt++;
            sleep(1);
        }
        return null;
    }

}
