<?php
namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/throw-test-exception', name: 'test_error')]
    public function testException(): Response {
        throw new \Exception("Erreur 500 de test");
    }

    #[Route('/throw-notfound-exception', name: 'test_notfound_error')]
    public function testNotFoundException(): Response {
        throw new NotFoundHttpException('Erreur 404');
    }

    #[Route('/throw-Logic-exception', name: 'test_notfound_error')]
    public function testLogicException(): Response {
        throw new LogicException('LogicException');
    }
}
