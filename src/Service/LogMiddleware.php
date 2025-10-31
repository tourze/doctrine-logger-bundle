<?php

namespace Tourze\DoctrineLoggerBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;

#[AsMiddleware]
readonly class LogMiddleware implements Middleware, ResetInterface
{
    public function __construct(
        private QueryExecutionTimeLogger $timeLogger,
        private Stopwatch $stopwatch,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new LogDriver($driver, $this->timeLogger, $this->stopwatch);
    }

    public function reset(): void
    {
        $this->stopwatch->reset();
    }
}
