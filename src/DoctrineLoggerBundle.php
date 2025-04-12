<?php

namespace Tourze\DoctrineLoggerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Middleware\LogMiddleware;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerBundle extends Bundle
{
    public function boot(): void
    {
        parent::boot();

        Backtrace::addProdIgnoreFiles((new \ReflectionClass(QueryExecutionTimeLogger::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogConnection::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogDriver::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogMiddleware::class))->getFileName());
        Backtrace::addProdIgnoreFiles((new \ReflectionClass(LogStatement::class))->getFileName());
    }
}
