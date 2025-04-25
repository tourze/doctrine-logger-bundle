<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Tourze\DoctrineLoggerBundle\Middleware\LogConnection;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogConnectionTest extends TestCase
{
    private LogConnection $connection;
    private Connection|MockObject $wrappedConnection;
    private QueryExecutionTimeLogger|MockObject $timeLogger;
    private Stopwatch|MockObject $stopwatch;
    private Statement|MockObject $statement;
    private Result|MockObject $result;

    protected function setUp(): void
    {
        $this->wrappedConnection = $this->createMock(Connection::class);
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
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
            ->willReturn($this->statement);

        $result = $this->connection->prepare($sql);

        $this->assertInstanceOf(LogStatement::class, $result);
    }

    public function testQuery(): void
    {
        $sql = 'SELECT * FROM users';
        $expectedSequenceId = '1';

        $this->timeLogger->expects($this->once())
            ->method('getSequenceId')
            ->willReturn($expectedSequenceId);

        $this->wrappedConnection->expects($this->once())
            ->method('query')
            ->with($sql)
            ->willReturn($this->result);

        $this->timeLogger->expects($this->once())
            ->method('watch')
            ->with("$expectedSequenceId. $sql", $sql, null)
            ->willReturnCallback(function ($name, $sql, $params, $callback) {
                return $callback();
            });

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
            ->willReturn($expectedSequenceId);

        $this->wrappedConnection->expects($this->once())
            ->method('exec')
            ->with($sql)
            ->willReturn($expectedReturn);

        $this->timeLogger->expects($this->once())
            ->method('watch')
            ->with("$expectedSequenceId. $sql", $sql, null)
            ->willReturnCallback(function ($name, $sql, $params, $callback) {
                return $callback();
            });

        $result = $this->connection->exec($sql);

        $this->assertSame($expectedReturn, $result);
    }

    public function testBeginTransaction(): void
    {
        // 开始事务
        $this->stopwatch->expects($this->once())
            ->method('start')
            ->with($this->stringContains('DBAL_PROFILE_TRANSACTION_'));

        $this->wrappedConnection->expects($this->once())
            ->method('beginTransaction');

        $this->connection->beginTransaction();
    }

    public function testCommitTransaction(): void
    {
        // 设置反射属性，模拟事务已开始
        $reflection = new \ReflectionObject($this->connection);
        $transactionIdsProperty = $reflection->getProperty('transactionIds');
        $transactionIdsProperty->setAccessible(true);
        $transactionIdsProperty->setValue($this->connection, ['test_transaction_id']);

        // 提交事务
        $event = $this->createMock(StopwatchEvent::class);

        $this->wrappedConnection->expects($this->once())
            ->method('commit');

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with('test_transaction_id')
            ->willReturn($event);

        $this->timeLogger->expects($this->once())
            ->method('checkEvent')
            ->with($event, $this->arrayHasKey('transactionId'), []);

        $this->connection->commit();
    }

    public function testRollBackTransaction(): void
    {
        // 设置反射属性，模拟事务已开始
        $reflection = new \ReflectionObject($this->connection);
        $transactionIdsProperty = $reflection->getProperty('transactionIds');
        $transactionIdsProperty->setAccessible(true);
        $transactionIdsProperty->setValue($this->connection, ['test_transaction_id']);

        // 回滚事务
        $event = $this->createMock(StopwatchEvent::class);

        $this->wrappedConnection->expects($this->once())
            ->method('rollBack');

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with('test_transaction_id')
            ->willReturn($event);

        $this->timeLogger->expects($this->once())
            ->method('checkEvent')
            ->with($event, $this->arrayHasKey('transactionId'));

        $this->connection->rollBack();
    }
}
