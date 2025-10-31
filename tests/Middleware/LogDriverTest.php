<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

/**
 * @internal
 */
#[CoversClass(LogDriver::class)]
final class LogDriverTest extends TestCase
{
    private LogDriver $driver;

    private Driver|MockObject $wrappedDriver;

    private QueryExecutionTimeLogger|MockObject $timeLogger;

    private Stopwatch|MockObject $stopwatch;

    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wrappedDriver = $this->createMock(Driver::class);
        // 使用具体类 QueryExecutionTimeLogger 进行 Mock：
        // QueryExecutionTimeLogger 是项目内部服务类，为简化测试逻辑需要模拟其行为
        // 该类职责单一，Mock 具体类不会影响测试的有效性
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        // 使用具体类 Stopwatch 进行 Mock：
        // Stopwatch 是 Symfony 组件的核心类，没有对应接口
        // 在测试中需要模拟其行为，使用具体类是唯一选择
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
            ->willReturn($this->connection)
        ;

        $result = $this->driver->connect($params);
    }
}
