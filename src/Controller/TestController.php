<?php
namespace App\Controller;

use LogicException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/throw-exception/{type}', name: 'test_exception')]
    public function testException(?string $type): Response 
    {
        switch($type){
            // erreur non reportées
            case 'notfound' : 
                throw new NotFoundHttpException('Erreur 404 in testController');
            case 'forbidden':
                throw new AccessDeniedHttpException('Accès refusé - Forbidden exception');
            case 'unauthorized':
                throw new UnauthorizedHttpException('Basic', 'Non autorisé - Unauthorized exception');

            // erreur 500 handlées
            case 'logic': 
                throw new LogicException('LogicException in testController');
            case 'runtime':
                throw new \RuntimeException('RuntimeException in testController');
            case null:
                throw new \Exception("Erreur 500 in testController");
            default:
                throw new \Exception("Exception inconnue ' in testController");

            // PHP warning
            case 'notice': 
                trigger_error("PHP erreur utilisateur", E_USER_NOTICE);
            // PHP critiques - arret script - FatalErrorHandler
            case 'critical': 
                trigger_error("Erreur critique", E_ERROR);
            case 'critical_user': 
                trigger_error("Erreur critique utilisateur", E_USER_ERROR);
        }
        throw new \Exception("Exception inconnue ' in testController");
    }
}
