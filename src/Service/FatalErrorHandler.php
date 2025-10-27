<?php
namespace App\Service;

use App\Message\ErrorReport;
use Symfony\Component\Messenger\MessageBusInterface;

class FatalErrorHandler
{
    public function __construct(private MessageBusInterface $bus)
    {
        register_shutdown_function([$this, 'handleFatalError']);
    }

    public function handleFatalError(): void
    {
        $error = error_get_last();
        if (!$error)  return;
        
        if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $report = new ErrorReport(
                serviceType: 'internal',
                scenario: 'fatal_error_shutdown',
                technicalContext: [
                    'file' => $error['file'],
                    'line' => $error['line'],
                ],
                message: $error['message'],
                stacktrace: '', 
                datas: []
            );
            $this->bus->dispatch($report);
        }
    }
}
