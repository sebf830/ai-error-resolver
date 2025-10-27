<?php
namespace App\Controller;

use App\Entity\ErrorLog;
use App\Message\ErrorReport;
use App\Service\JwtToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ErrorApiController extends AbstractController
{
    public function __construct(
        private JwtToken $jwt
    ){}

    #[Route('/api/error', name: 'api_create_error', methods: ['POST'])]
    public function __invoke(Request $request, MessageBusInterface $bus): JsonResponse
    {
        if(!$this->authenticateRequest($request)) {
            return new JsonResponse(['status' => 'ko', 'message' => 'Missing or invalid Authorization/token'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $error = new ErrorReport(
            serviceType: $data['serviceType'] ?? 'unknown',
            scenario: $data['scenario'] ?? '',
            message: $data['message'] ?? '',
            stacktrace: $data['stacktrace'] ?? '',
            technicalContext: json_encode($data['technicalContext']) ?? [],
            datas: json_encode($data['datas']) ?? []
        );

        $bus->dispatch($error); 

        return new JsonResponse(['status' => 'ok', 'message' => 'Erreur en cours de traitement']);
    }

    #[Route('/api/errors', name: 'api_get_errors', methods: ['GET'])]
    public function list(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if(!$this->authenticateRequest($request)) {
            return new JsonResponse(['status' => 'ko', 'message' => 'Missing or invalid Authorization/token'], 401);
        }

        $logs = $em->getRepository(ErrorLog::class)->findBy([], ['createdAt' => 'DESC'], 50);

        return new JsonResponse(array_map(fn($log) => [
            'id' => $log->getId(),
            'time' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            'serviceType' => $log->getServiceType(),
            'message' => $log->getMessage() ?? 'undefined',
            'trace' => $log->getStacktrace() ?? 'undefined',
            'scenario' => $log->getScenario() ?? 'undefined',
            'technicalContext' => is_array($log->getTechnicalContext()) ? json_encode($log->getTechnicalContext()) :  'aucun contexte',
            'solution' => $log->getDatas()['solution'] ?? 'aucune solution',
        ], $logs), 200);
    }

    private function authenticateRequest($request){
        $header = $request->headers->get('Authorization'); 
        if (!$header || !str_starts_with($header, 'Bearer ')) return false;

        return $this->jwt->verifyToken(substr($header, 7));
    }
}