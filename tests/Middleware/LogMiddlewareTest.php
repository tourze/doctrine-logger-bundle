<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Middleware\LogMiddleware;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogMiddlewareTest extends TestCase
{
    private LogMiddleware $middleware;
    private QueryExecutionTimeLogger|MockObject $timeLogger;
    private Stopwatch|MockObject $stopwatch;
    private Driver|MockObject $driver;

    protected function setUp(): void
    {
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->driver = $this->createMock(Driver::class);

        $this->middleware = new LogMiddleware($this->timeLogger, $this->stopwatch);
    }

    public function testWrap(): void
    {
        $result = $this->middleware->wrap($this->driver);

        $this->assertInstanceOf(LogDriver::class, $result);
        $this->assertInstanceOf(Driver::class, $this->driver);
    }

    public function testReset(): void
    {
        $this->stopwatch->expects($this->once())
            ->method('reset');

        $this->middleware->reset();
    }

}
