<?php
namespace App\EventSubscriber;

use App\Message\ErrorReport;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MessageBusInterface $bus, 
        private KernelInterface $kernel,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();
        
        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) return; 

        $this->logger?->info("Error received in subscriber: " . $e->getMessage());

        $cleanDatas = array_map(function($v, $k) {
            if (in_array($k, ['password', 'token', 'api_key', 'jwt'])) return '***';
            if (is_string($v)) return mb_substr($v, 0, 200);
            return $v;
        }, $event->getRequest()->request->all(), array_keys($event->getRequest()->request->all()));

        $error = new ErrorReport(
            serviceType: 'Symfony internal',
            technicalContext: [
                'env' => $this->kernel->getEnvironment(),
                'symfony_version' => Kernel::VERSION,
                'php_version' => phpversion(),
                'user_agent' => $event->getRequest()->headers->get('User-Agent'),
                'ip' => $event->getRequest()->getClientIp(),
            ],
            scenario: 
                 "Requete : {$event->getRequest()->getMethod()}\n" . 
                 "uri : {$event->getRequest()->getRequestUri()}\n" . 
                 "route: {$event->getRequest()->attributes->get('_route')}\n" . 
                 "controller : {$event->getRequest()->attributes->get('_controller')}\n",
            
            message: $e->getMessage(),
            stacktrace: implode("\n", array_slice(explode("\n", $e->getTraceAsString()), 0, 50)),

            datas : [
                'query_params' => $event->getRequest()->query->all(),
                'request_body' => $cleanDatas,
                'cookies' => $event->getRequest()->cookies->all(),
                'session' => $event->getRequest()->getSession()?->all() ?? []
            ]
        );
         $this->bus->dispatch($error);
    }
}
