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

    public function resolve(ErrorLog $errorLog): ?string
    {
        $subkey = substr($this->open_api_key, 0, 20);
        $this->logger?->info("Received in GPT resolver with OPENAPIKEY : {$subkey }");

        $stringDatas = json_encode($errorLog->getDatas());

        $serviceType = addslashes($errorLog->getServiceType());
        $message = addslashes($errorLog->getMessage());
        $stacktrace = addslashes($errorLog->getStacktrace());
        $stringDatas = addslashes($stringDatas);


         try {
            $prompt = "Voici l'erreur suivante." . 
            "service: {$serviceType}\n" .
            "message: {$message} \n" .
            "Stacktrace: {$stacktrace}\n" .
            "Contexte: {$stringDatas}.\n" .
            "Propose une solution précise et une solution alternative à très forte probabilité.\n" .
            "Sois conçis et précis dans ta réponse.\n" . 
            "Répond avec une string JSON correctement échappée pour être stockée telle quelle en base et décodée avec json_decode() :\n" . 
            'Format [
                {
                    "solution_principale": {
                    "explication_erreur": "",
                    "explication_correctif": "",
                    "code_correctif": ""
                    }
                },
                {
                    "solution_alternative": {
                    "explication_erreur": "",
                    "explication_correctif": "",
                    "code_correctif": ""
                    }
                }
            ]' .
            "[{ \"solution_principale\" : { \"explication_erreur\" : \"\", \"explication_correctif\": \"\",  \"code_correctif\" : \"\" }},{\"solution_alternative\" : { \"explication_erreur\" : \"\", \"explication_correctif\": \"\",  \"code_correctif\" : \"\" }}]" .
            "Entoure chaque portion de code par une balise <code></code>,\n" .
            "Ajoute un \\ avant les $ ou tout autre caractère qui peut casser la string coté back. 
            ";

            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'timeout' => 8,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->open_api_key,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ]
            ]);

            $data = $response->toArray(false);
            $content = $data['choices'][0]['message']['content'] ?? null;

            if ($content) {
                $this->logger->info("Réponse GPT reçue", ['response' => $content]);
                return $content;
            }

            } catch (\Throwable $e) {
                $this->logger->error("Erreur pendant la réponse de ChatGPT : {$e->getMessage()}");
                return null;
            }
        return null;
    }
}
