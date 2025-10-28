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

         try {
            $prompt = "Voici l'erreur suivante :\n" .
            "Service: {$errorLog->getServiceType()}\n" .
            "Message: {$errorLog->getMessage()}\n" .
            "Stacktrace: {$errorLog->getStacktrace()}\n" .
            "Contexte: {$stringDatas}\n\n" .
            "Propose : 1 solution principale et 1 solution alternative.\n" .
            "Sois concis et précis.\n" .
            "Répond avec un JSON strictement valide (compatible PHP/JavaScript) :\n" .
            " - Structure : un tableau à 2 objets : solution_principale et solution_alternative.\n" .
            " - Chaque objet contient : explication_erreur, explication_correctif, code_correctif.\n" .
            " - Entoure les portions de code par <code></code>.\n" .
            " - Ne mets pas de backslash devant $ ou d'autres caractères.\n" .
            " - Utilise \\n pour les retours à la ligne dans les chaînes si nécessaire.\n" .
            "- Pour chaque code_correctif, conserve les sauts de ligne et les indentations.\n" .
            "- Représente les retours à la ligne par \n dans le JSON.\n" .
            "- Utilise 2 espaces pour l'indentation dans le code à l'intérieur des chaînes.\n" .
            "- Entoure toujours le code par <code></code>.\n" .
            "Format attendu :\n" .
            "[\n" .
            "  {\"solution_principale\":{\"explication_erreur\":\"\",\"explication_correctif\":\"\",\"code_correctif\":\"\"}},\n" .
            "  {\"solution_alternative\":{\"explication_erreur\":\"\",\"explication_correctif\":\"\",\"code_correctif\":\"\"}}\n" .
            "]";


            $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'timeout' => 15,
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
