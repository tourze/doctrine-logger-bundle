<?php

namespace Tourze\DoctrineLoggerBundle\Middleware;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsMiddleware;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

#[AsMiddleware(connections: [
    'default',
    'session',
])]
class LogMiddleware implements Middleware, ResetInterface
{
    public function __construct(
        private readonly QueryExecutionTimeLogger $timeLogger,
        private readonly Stopwatch $stopwatch,
    )
    {
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
