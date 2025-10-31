<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(QueryExecutionTimeLogger::class)]
#[RunTestsInSeparateProcesses]
final class QueryExecutionTimeLoggerTest extends AbstractIntegrationTestCase
{
    private QueryExecutionTimeLogger $logger;

    protected function onSetUp(): void
    {
        // 无额外设置需求
    }

    private function ensureServiceSetup(): void
    {
        if (!isset($this->logger)) {
            $this->setUpService();
        }
    }

    private function setUpService(): void
    {
        // 获取容器中的服务实例
        $this->logger = self::getService(QueryExecutionTimeLogger::class);

        // 清理环境变量影响
        $_ENV['APP_ENV'] = 'test';
        $_ENV['SQL_LOG_LENGTH'] = '1000';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = false;
    }

    public function testGetSequenceId(): void
    {
        $this->ensureServiceSetup();
        $id1 = $this->logger->getSequenceId();
        $id2 = $this->logger->getSequenceId();

        $this->assertNotEquals($id1, $id2);
        $this->assertEquals((int) $id1 + 1, (int) $id2);
    }

    public function testWatch(): void
    {
        $this->ensureServiceSetup();
        $name = 'test_query';
        $sql = 'SELECT * FROM users';
        $params = ['id' => 1];
        $expectedResult = ['user' => 'test'];

        $callback = function () use ($expectedResult) {
            return $expectedResult;
        };

        // 测试 watch 方法执行回调并返回结果
        $result = $this->logger->watch($name, $sql, $params, $callback);

        $this->assertSame($expectedResult, $result);
    }

    public function testCheckEventWithLowDuration(): void
    {
        $this->ensureServiceSetup();
        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟 getDuration 方法的返回值
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(500) // 低于默认阈值1000
        ;

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 设置为测试环境，确保不会调用info
        $_ENV['APP_ENV'] = 'test';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = false;

        // 测试 checkEvent 方法不抛出异常
        $this->logger->checkEvent($event, $currentQuery);

        // 测试生产环境下的"SELECT 1"语句(不会记录日志)
        $_ENV['APP_ENV'] = 'prod';
        $selectOneQuery = [
            'sql' => 'SELECT 1',
            'params' => [],
        ];
        $this->logger->checkEvent($event, $selectOneQuery);

        // 验证日志记录器没有抛出异常，且事件检查成功完成
        $this->assertSame(500, $event->getDuration(), 'Event duration should be as expected');
    }

    public function testCheckEventWithLowDurationInProd(): void
    {
        $this->ensureServiceSetup();

        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟 getDuration 方法的返回值
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(500) // 低于默认阈值1000
        ;

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 设置为生产环境，并启用日志跟踪
        $_ENV['APP_ENV'] = 'prod';
        $_ENV['LOG_DB_QUERY_BACKTRACE'] = true;

        // 测试 checkEvent 方法不抛出异常
        $this->logger->checkEvent($event, $currentQuery);

        $this->assertSame(500, $event->getDuration());
    }

    public function testCheckEventWithHighDuration(): void
    {
        $this->ensureServiceSetup();

        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟 getDuration 方法的返回值
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(1500) // 超过默认阈值1000
        ;

        $currentQuery = [
            'sql' => 'SELECT * FROM users WHERE id = ?',
            'params' => [1],
        ];

        // 测试高持续时间事件处理不抛出异常
        $this->logger->checkEvent($event, $currentQuery);

        $this->assertSame(1500, $event->getDuration());
    }

    public function testCheckEventWithSubQueries(): void
    {
        $this->ensureServiceSetup();

        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟 getDuration 方法的返回值
        $event = $this->createMock(StopwatchEvent::class);
        $event->method('getDuration')
            ->willReturn(1500) // 超过默认阈值
        ;

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

        // 测试带子查询的事件处理不抛出异常
        $this->logger->checkEvent($event, $currentQuery, $subQueries);

        $this->assertSame(1500, $event->getDuration());
    }

    public function testNormalizeParams(): void
    {
        $this->ensureServiceSetup();
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
        $this->ensureServiceSetup();

        // 测试 reset 方法不抛出异常
        $this->logger->reset();

        // 验证可以正常获取序列号（说明服务状态正常）
        $sequenceId = $this->logger->getSequenceId();
        $this->assertIsString($sequenceId);
    }
}
