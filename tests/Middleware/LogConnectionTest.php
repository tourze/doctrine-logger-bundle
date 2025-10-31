<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

/**
 * @internal
 */
#[CoversClass(LogConnection::class)]
final class LogConnectionTest extends TestCase
{
    private LogConnection $connection;

    private Connection|MockObject $wrappedConnection;

    private QueryExecutionTimeLogger|MockObject $timeLogger;

    private Stopwatch|MockObject $stopwatch;

    private Statement|MockObject $statement;

    private Result|MockObject $result;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wrappedConnection = $this->createMock(Connection::class);
        // 使用具体类 QueryExecutionTimeLogger 进行 Mock：
        // QueryExecutionTimeLogger 是项目内部服务类，为简化测试逻辑需要模拟其行为
        // 该类职责单一，Mock 具体类不会影响测试的有效性
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        // 使用具体类 Stopwatch 进行 Mock：
        // Stopwatch 是 Symfony 组件的核心类，没有对应接口
        // 在测试中需要模拟其行为，使用具体类是唯一选择
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->statement = $this->createMock(Statement::class);
        $this->result = $this->createMock(Result::class);

        $this->connection = new LogConnection(
            $this->wrappedConnection,
            $this->timeLogger,
            $this->stopwatch
        );
    }

    public function testPrepare(): void
    {
        $sql = 'SELECT * FROM users WHERE id = ?';

        $this->wrappedConnection->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->statement)
        ;

        $result = $this->connection->prepare($sql);

        $this->assertInstanceOf(LogStatement::class, $result);
    }

    public function testQuery(): void
    {
        $sql = 'SELECT * FROM users';
        $expectedSequenceId = '1';

        $this->timeLogger->expects($this->once())
            ->method('getSequenceId')
            ->willReturn($expectedSequenceId)
        ;

        $this->wrappedConnection->expects($this->once())
            ->method('query')
            ->with($sql)
            ->willReturn($this->result)
        ;

        $expectedName = "{$expectedSequenceId}. {$sql}";
        $this->timeLogger->expects($this->once())
            ->method('watch')
            ->with($expectedName, $sql, null)
            ->willReturnCallback(function (string $watchName, string $watchSql, ?array $watchParams, callable $watchCallback) {
                return $watchCallback();
            })
        ;

        $result = $this->connection->query($sql);

        $this->assertSame($this->result, $result);
    }

    public function testExec(): void
    {
        $sql = 'UPDATE users SET name = "test" WHERE id = 1';
        $expectedSequenceId = '2';
        $expectedReturn = 1;

        $this->timeLogger->expects($this->once())
            ->method('getSequenceId')
            ->willReturn($expectedSequenceId)
        ;

        $this->wrappedConnection->expects($this->once())
            ->method('exec')
            ->with($sql)
            ->willReturn($expectedReturn)
        ;

        $expectedName = "{$expectedSequenceId}. {$sql}";
        $this->timeLogger->expects($this->once())
            ->method('watch')
            ->with($expectedName, $sql, null)
            ->willReturnCallback(function (string $watchName, string $watchSql, ?array $watchParams, callable $watchCallback) {
                return $watchCallback();
            })
        ;

        $result = $this->connection->exec($sql);

        $this->assertSame($expectedReturn, $result);
    }

    public function testBeginTransaction(): void
    {
        // 开始事务
        $this->stopwatch->expects($this->once())
            ->method('start')
            ->with(self::stringContains('DBAL_PROFILE_TRANSACTION_'))
        ;

        $this->wrappedConnection->expects($this->once())
            ->method('beginTransaction')
        ;

        $this->connection->beginTransaction();
    }

    public function testCommitTransaction(): void
    {
        // 设置反射属性，模拟事务已开始
        $reflection = new \ReflectionObject($this->connection);
        $transactionIdsProperty = $reflection->getProperty('transactionIds');
        $transactionIdsProperty->setAccessible(true);
        $transactionIdsProperty->setValue($this->connection, ['test_transaction_id']);

        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟事件的返回值，使用具体类是必要的选择
        $event = $this->createMock(StopwatchEvent::class);

        $this->wrappedConnection->expects($this->once())
            ->method('commit')
        ;

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with('test_transaction_id')
            ->willReturn($event)
        ;

        $this->timeLogger->expects($this->once())
            ->method('checkEvent')
            ->with($event, self::arrayHasKey('transactionId'), [])
        ;

        $this->connection->commit();
    }

    public function testRollBackTransaction(): void
    {
        // 设置反射属性，模拟事务已开始
        $reflection = new \ReflectionObject($this->connection);
        $transactionIdsProperty = $reflection->getProperty('transactionIds');
        $transactionIdsProperty->setAccessible(true);
        $transactionIdsProperty->setValue($this->connection, ['test_transaction_id']);

        // 使用具体类 StopwatchEvent 进行 Mock：
        // StopwatchEvent 是 Symfony Stopwatch 组件的事件类，没有对应接口
        // 测试需要模拟事件的返回值，使用具体类是必要的选择
        $event = $this->createMock(StopwatchEvent::class);

        $this->wrappedConnection->expects($this->once())
            ->method('rollBack')
        ;

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with('test_transaction_id')
            ->willReturn($event)
        ;

        $this->timeLogger->expects($this->once())
            ->method('checkEvent')
            ->with($event, self::arrayHasKey('transactionId'))
        ;

        $this->connection->rollBack();
    }
}
