<?php

namespace Service;

use Doctrine\DBAL\Driver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Service\LogMiddleware;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

/**
 * @internal
 */
#[CoversClass(LogMiddleware::class)]
final class LogMiddlewareTest extends TestCase
{
    private LogMiddleware $middleware;

    private QueryExecutionTimeLogger|MockObject $timeLogger;

    private Stopwatch|MockObject $stopwatch;

    private Driver&MockObject $driver;

    protected function setUp(): void
    {
        parent::setUp();
        // 使用具体类 QueryExecutionTimeLogger 进行 Mock：
        // QueryExecutionTimeLogger 是项目内部服务类，为简化测试逻辑需要模拟其行为
        // 该类职责单一，Mock 具体类不会影响测试的有效性
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        // 使用具体类 Stopwatch 进行 Mock：
        // Stopwatch 是 Symfony 组件的核心类，没有对应接口
        // 在测试中需要模拟其行为，使用具体类是唯一选择
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
            ->method('reset')
        ;

        $this->middleware->reset();
    }
}
