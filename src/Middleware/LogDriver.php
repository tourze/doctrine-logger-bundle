<?php

declare(strict_types=1);

namespace Tourze\DoctrineLoggerBundle\Middleware;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogDriver extends AbstractDriverMiddleware
{
    /** @internal This driver can be only instantiated by its middleware. */
    public function __construct(
        DriverInterface $driver,
        private readonly QueryExecutionTimeLogger $timeLogger,
        private readonly Stopwatch $stopwatch,
    ) {
        parent::__construct($driver);
    }

    public function connect(
        #[\SensitiveParameter] array $params
    ): LogConnection {
        return new LogConnection(
            parent::connect($params),
            $this->timeLogger,
            $this->stopwatch,
        );
    }
}
