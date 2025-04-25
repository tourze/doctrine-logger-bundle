<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class QueryExecutionTimeLoggerTest extends TestCase
{
    private QueryExecutionTimeLogger $logger;
    private LoggerInterface|MockObject $sqlLogger;
    private Stopwatch|MockObject $stopwatch;

    protected function setUp(): void
    {
        $this->sqlLogger = $this->createMock(LoggerInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->logger = new QueryExecutionTimeLogger($this->sqlLogger, $this->stopwatch);

        // 清理环境变量影响
        $_ENV['APP_ENV'] = 'test';
        $_ENV['SQL_LOG_LENGTH'] = '1000';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = false;
    }

    public function testGetSequenceId(): void
    {
        $id1 = $this->logger->getSequenceId();
        $id2 = $this->logger->getSequenceId();

        $this->assertNotEquals($id1, $id2);
        $this->assertEquals((int)$id1 + 1, (int)$id2);
    }

    public function testWatch(): void
    {
        $name = 'test_query';
        $sql = 'SELECT * FROM users';
        $params = ['id' => 1];
        $expectedResult = ['user' => 'test'];

        $event = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->expects($this->once())
            ->method('start')
            ->with($name);

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with($name)
            ->willReturn($event);

        $callback = function () use ($expectedResult) {
            return $expectedResult;
        };

        $result = $this->logger->watch($name, $sql, $params, $callback);

        $this->assertSame($expectedResult, $result);
    }

    public function testCheckEventWithLowDuration(): void
    {
        // 不限制 getDuration 调用次数
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(500); // 低于默认阈值1000

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 设置为测试环境，确保不会调用info
        $_ENV['APP_ENV'] = 'test';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = false;

        $this->sqlLogger->expects($this->never())->method('info');
        $this->logger->checkEvent($event, $currentQuery);

        // 测试生产环境下的"SELECT 1"语句(不会记录日志)
        $_ENV['APP_ENV'] = 'prod';
        $selectOneQuery = [
            'sql' => 'SELECT 1',
            'params' => [],
        ];
        $this->sqlLogger->expects($this->never())->method('info');
        $this->logger->checkEvent($event, $selectOneQuery);
    }

    public function testCheckEventWithLowDurationInProd(): void
    {
        // 不限制 getDuration 调用次数
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(500); // 低于默认阈值1000

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 设置为生产环境，并启用日志跟踪
        $_ENV['APP_ENV'] = 'prod';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = true;

        $this->sqlLogger->expects($this->once())
            ->method('info')
            ->with(
                $this->equalTo('执行SQL'),
                $this->callback(function ($context) {
                    return isset($context['executionTime'])
                        && isset($context['sql'])
                        && isset($context['params'])
                        && isset($context['backtrace']);
                })
            );

        $this->logger->checkEvent($event, $currentQuery);
    }

    public function testCheckEventWithHighDuration(): void
    {
        // 不限制 getDuration 调用次数
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(1500); // 超过默认阈值1000

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 超过阈值的查询应记录error级别日志
        $this->sqlLogger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('执行SQL时发现可能超时的查询'),
                $this->callback(function ($context) {
                    return isset($context['executionTime'])
                        && isset($context['sql'])
                        && isset($context['params'])
                        && isset($context['backtrace']);
                })
            );

        $this->logger->checkEvent($event, $currentQuery);
    }

    public function testCheckEventWithSubQueries(): void
    {
        // 不限制 getDuration 调用次数
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(1500); // 超过默认阈值

        $currentQuery = [
            'sql' => 'SELECT * FROM users',
            'params' => [],
        ];

        $subQueries = [
            [
                'sql' => 'SELECT * FROM profiles WHERE user_id = ?',
                'params' => [1],
            ],
        ];

        $this->sqlLogger->expects($this->once())
            ->method('error');

        $this->sqlLogger->expects($this->once())
            ->method('warning')
            ->with(
                $this->equalTo('记录超时查询发生时的子查询'),
                $this->equalTo($subQueries[0])
            );

        $this->logger->checkEvent($event, $currentQuery, $subQueries);
    }

    public function testNormalizeParams(): void
    {
        $method = new \ReflectionMethod($this->logger, 'normalizeParams');
        $method->setAccessible(true);

        $params = [
            'normal' => 'value',
            'binary' => "\x80\x81\x82", // 非UTF-8字符
            'long' => str_repeat('a', 50), // 超过32个字符的长度
            'nested' => [
                'long' => str_repeat('b', 50),
            ],
        ];

        $result = $method->invoke($this->logger, $params);

        $this->assertEquals('value', $result['normal']);
        $this->assertEquals(QueryExecutionTimeLogger::BINARY_DATA_VALUE, $result['binary']);
        $this->assertStringEndsWith(' [...]', $result['long']);
        $this->assertStringEndsWith(' [...]', $result['nested']['long']);
    }

    public function testReset(): void
    {
        $this->stopwatch->expects($this->once())
            ->method('reset');

        $this->logger->reset();
    }
}
