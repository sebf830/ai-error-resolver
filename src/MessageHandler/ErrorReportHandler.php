<?php
namespace App\MessageHandler;

use App\Message\ErrorReport;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use App\Entity\ErrorLog;
use App\Service\ChatGptResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsMessageHandler]
class ErrorReportHandler 
{
    public function __construct(
        private EntityManagerInterface $em,
        private ChatGptResolver $chatGptResolver,
        private HubInterface $mercureHub,
        private LoggerInterface $logger ,
        private ManagerRegistry $doctrine
    ) {}

    public function __invoke(ErrorReport $error)
    {
        $this->logger?->info("Received in ErrorReportHandler: " . $error->message);

        if (!$this->em->isOpen()) $this->em = $this->doctrine->resetManager();

        $log = new ErrorLog();
        $log->setServiceType(trim($error->serviceType));
        $log->setScenario(trim($error->scenario));
        $log->setTechnicalContext(is_array($error->technicalContext) ? $error->technicalContext : []);
        $log->setMessage(trim($error->message));
        $log->setStacktrace(trim($error->stacktrace));
        $log->setCreatedAt(new \DateTimeImmutable());
        $log->setDatas(is_array($error->datas) ? $error->datas : []);
        $this->em->persist($log);
        $this->em->flush();

        $payload = [
            'id' => $log->getId(),
            'time' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            'serviceType' => $error->serviceType,
            'message' => (string)$log->getMessage(),
            'trace' => (string)$log->getStacktrace() ?? 'undefined',
            'scenario' => (string)$log->getScenario() ?? 'undefined',
            'technicalContext' => is_array($log->getTechnicalContext()) ? json_encode($log->getTechnicalContext()) :  'aucun contexte',
            'datas' => $log->getDatas() ? json_encode($log->getDatas()) : 'undefined',
            'solution' => '',
        ];

        try {
            $solution = $this->chatGptResolver->resolve($log) ?? "Failure or no response from GPT";
            $solution = str_replace('```json', '', $solution);
            $solution = str_replace('```', '', $solution);
            $solution = trim($solution);

            // Save in correct json format
            $log->setSolution($solution);
            $this->em->flush();

            // Send in html format
            $payload['solution'] = $log->getSolution();
            $update = new Update('https://example.com/errors',  json_encode($payload));

            $this->mercureHub->publish($update);

        } catch (\Throwable $e) {
            $payload['solution'] = 'Failure or no response from GPT';

            try{
                $this->mercureHub->publish(new Update('https://example.com/errors', json_encode($payload)));
                } catch (\Throwable $secondeAttempt) {
                    $this->logger?->info("Fail to send second attempt via mercure : {$secondeAttempt->getMessage()}, initialError : {$error->message}");
                }
        }
    }
}
