<?php

namespace Tourze\DoctrineLoggerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();

        Backtrace::addProdIgnoreFiles((new \ReflectionClass(QueryExecutionTimeLogger::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogStatement::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogConnection::class))->getFileName());
    }
}
