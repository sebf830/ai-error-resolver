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
        private ManagerRegistry $doctrine,
    ) {}

    public function __invoke(ErrorReport $error)
    {
        $this->logger?->info("Received in ErrorReportHandler: " . $error->message);

        if (!$this->em->isOpen()) $this->em = $this->doctrine->resetManager();

        // 1️⃣ Persister l'erreur en DB
        $log = new ErrorLog();
        $log->setServiceType($error->serviceType);
        $log->setScenario($error->scenario);
        $log->setTechnicalContext(is_array($error->technicalContext) ? $error->technicalContext : []);
        $log->setMessage($error->message);
        $log->setStacktrace($error->stacktrace);
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
            'solution' => '',
            'status' => ''
        ];

        // 2️⃣ Appel ChatGPT pour obtenir une solution
        try {
            $solution = $this->chatGptResolver->resolveWithretry($log, 2);
            $solutionString = $solution === null ? "Failure or no response from GPT" : json_encode($solution);

            $datas = $log->getDatas() ?? [];
            
            $datas = array_merge($datas, ['solution' => $solutionString]);
            $log->setDatas($datas);

            $this->em->flush();

            $payload['solution'] = $solutionString ?? 'aucune solution';
            $update = new Update('https://example.com/errors',  json_encode($payload));

            $this->mercureHub->publish($update);

        } catch (\Throwable $e) {
            $payload['solution'] = 'aucune solution';

            try{
                $this->mercureHub->publish(new Update('https://example.com/errors', json_encode($payload)));
                } catch (\Throwable $secondeAttempt) {
                    $this->logger?->info("Fail to send second attempt via mercure : {$secondeAttempt->getMessage()}, initialError : {$error->message}");
                }
        }
    }
}
