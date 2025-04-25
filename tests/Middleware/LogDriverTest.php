<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogDriverTest extends TestCase
{
    private LogDriver $driver;
    private Driver|MockObject $wrappedDriver;
    private QueryExecutionTimeLogger|MockObject $timeLogger;
    private Stopwatch|MockObject $stopwatch;
    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        $this->wrappedDriver = $this->createMock(Driver::class);
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->connection = $this->createMock(Connection::class);

        $this->driver = new LogDriver(
            $this->wrappedDriver,
            $this->timeLogger,
            $this->stopwatch
        );
    }

    public function testConnect(): void
    {
        $params = ['host' => 'localhost', 'user' => 'root', 'password' => 'secret'];

        $this->wrappedDriver->expects($this->once())
            ->method('connect')
            ->with($params)
            ->willReturn($this->connection);

        $result = $this->driver->connect($params);

        $this->assertInstanceOf(LogConnection::class, $result);
    }
}
