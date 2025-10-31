<?php

namespace Tourze\DoctrineLoggerBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            MonologBundle::class => ['all' => true],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        $filename = (new \ReflectionClass(QueryExecutionTimeLogger::class))->getFileName();
        if (false !== $filename) {
            Backtrace::addProdIgnoreFiles($filename);
        }

        $filename = (new \ReflectionClass(LogStatement::class))->getFileName();
        if (false !== $filename) {
            Backtrace::addProdIgnoreFiles($filename);
        }

        $filename = (new \ReflectionClass(LogConnection::class))->getFileName();
        if (false !== $filename) {
            Backtrace::addProdIgnoreFiles($filename);
        }
    }
}
