<?php
namespace App\Message;

class ErrorReport
{
    public function __construct(
        public string $serviceType,
        public string $scenario,  
        public array $technicalContext,
        public string $message,
        public string $stacktrace,
        public array $datas = [],
    ) {}
}
