<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Middleware;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineLoggerBundle\Middleware\LogStatement;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

/**
 * @internal
 */
#[CoversClass(LogStatement::class)]
final class LogStatementTest extends TestCase
{
    private LogStatement $statement;

    private Statement|MockObject $wrappedStatement;

    private QueryExecutionTimeLogger|MockObject $timeLogger;

    private Result|MockObject $result;

    private string $sql;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wrappedStatement = $this->createMock(Statement::class);
        // 使用具体类 QueryExecutionTimeLogger 进行 Mock：
        // QueryExecutionTimeLogger 是项目内部服务类，为简化测试逻辑需要模拟其行为
        // 该类职责单一，Mock 具体类不会影响测试的有效性
        $this->timeLogger = $this->createMock(QueryExecutionTimeLogger::class);
        $this->result = $this->createMock(Result::class);
        $this->sql = 'SELECT * FROM users WHERE id = ?';

        $this->statement = new LogStatement(
            $this->wrappedStatement,
            $this->timeLogger,
            $this->sql
        );
    }

    public function testBindValue(): void
    {
        $param = 1;
        $value = 42;
        $type = ParameterType::INTEGER;

        $this->wrappedStatement->expects($this->once())
            ->method('bindValue')
            ->with($param, $value, $type)
        ;

        $this->statement->bindValue($param, $value, $type);

        // 验证绑定的参数已存储
        $reflectionProperty = new \ReflectionProperty(LogStatement::class, 'params');
        $reflectionProperty->setAccessible(true);
        $params = $reflectionProperty->getValue($this->statement);

        $this->assertArrayHasKey($param, $params);
        $this->assertEquals($value, $params[$param]);
    }

    public function testExecute(): void
    {
        $expectedSequenceId = '1';
        $params = [1 => 42, 2 => 'test'];

        // 绑定一些参数，确保使用第三个参数
        $this->statement->bindValue(1, 42, ParameterType::INTEGER);
        $this->statement->bindValue(2, 'test', ParameterType::STRING);

        $this->timeLogger->expects($this->once())
            ->method('getSequenceId')
            ->willReturn($expectedSequenceId)
        ;

        $this->wrappedStatement->expects($this->once())
            ->method('execute')
            ->willReturn($this->result)
        ;

        $expectedName = "{$expectedSequenceId}. {$this->sql}";
        $this->timeLogger->expects($this->once())
            ->method('watch')
            ->with($expectedName, $this->sql, $params)
            ->willReturnCallback(function (string $watchName, string $watchSql, ?array $watchParams, callable $watchCallback) {
                return $watchCallback();
            })
        ;

        $result = $this->statement->execute();

        $this->assertSame($this->result, $result);
    }
}
