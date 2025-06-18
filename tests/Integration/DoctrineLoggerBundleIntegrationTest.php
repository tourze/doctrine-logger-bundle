<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Integration;

use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Middleware\LogDriver;
use Tourze\DoctrineLoggerBundle\Middleware\LogMiddleware;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerBundleIntegrationTest extends TestCase
{
    private ContainerBuilder $container;
    private LoggerInterface $logger;
    private Stopwatch $stopwatch;
    private QueryExecutionTimeLogger $timeLogger;
    private LogMiddleware $middleware;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->container->set('logger', $this->logger);

        $this->stopwatch = new Stopwatch();
        $this->container->set('stopwatch', $this->stopwatch);

        $this->timeLogger = new QueryExecutionTimeLogger($this->logger, $this->stopwatch);
        $this->container->set(QueryExecutionTimeLogger::class, $this->timeLogger);

        $this->middleware = new LogMiddleware($this->timeLogger, $this->stopwatch);
        $this->container->set(LogMiddleware::class, $this->middleware);

        // 设置环境变量
        $_ENV['APP_ENV'] = 'test';
        $_ENV['SQL_LOG_LENGTH'] = '1000';
    }

    public function testMiddlewareWrapsDriver(): void
    {
        $driverMock = $this->createMock(\Doctrine\DBAL\Driver::class);

        $wrappedDriver = $this->middleware->wrap($driverMock);

        $this->assertInstanceOf(LogDriver::class, $wrappedDriver);
        $this->assertInstanceOf(AbstractDriverMiddleware::class, $wrappedDriver);
    }

    public function testTimeLoggerWatchesQuery(): void
    {
        $name = 'test_query';
        $sql = 'SELECT * FROM users';
        $params = ['id' => 1];
        $callbackResult = 'result';

        $this->logger->expects($this->never())->method('error');

        $result = $this->timeLogger->watch($name, $sql, $params, function () use ($callbackResult) {
            return $callbackResult;
        });

        $this->assertSame($callbackResult, $result);
    }

    public function testTimeLoggerSequenceId(): void
    {
        $id1 = $this->timeLogger->getSequenceId();
        $id2 = $this->timeLogger->getSequenceId();

        $this->assertIsString($id1);
        $this->assertIsString($id2);
        $this->assertNotEquals($id1, $id2);
    }

    public function testTimeLoggerWatchesSlow(): void
    {
        $name = 'slow_query';
        $sql = 'SELECT * FROM large_table';
        $params = [];

        // 创建模拟对象
        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockEvent = $this->createMock(\Symfony\Component\Stopwatch\StopwatchEvent::class);

        // 设置模拟对象行为
        $mockEvent->method('getDuration')
            ->willReturn(1500); // 直接让getDuration返回超过阈值的值

        $mockLogger->expects($this->once())->method('error');

        // 直接使用模拟对象创建 QueryExecutionTimeLogger
        $mockTimeLogger = new QueryExecutionTimeLogger($mockLogger, $this->stopwatch);

        // 直接调用 checkEvent 方法，不需要调用 stopwatch.stop
        $mockTimeLogger->checkEvent($mockEvent, [
            'sql' => $sql,
            'params' => $params,
        ]);
    }
}
