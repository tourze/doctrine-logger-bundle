<?php

namespace Tourze\DoctrineLoggerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();

        Backtrace::addProdIgnoreFiles((new \ReflectionClass(QueryExecutionTimeLogger::class))->getFileName());
    }
}
