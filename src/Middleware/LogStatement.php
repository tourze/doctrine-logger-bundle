<?php

declare(strict_types=1);

namespace Tourze\DoctrineLoggerBundle\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogStatement extends AbstractStatementMiddleware
{
    /** @var array<int,mixed>|array<string,mixed> */
    private array $params = [];

    /** @internal This statement can be only instantiated by its connection. */
    public function __construct(
        StatementInterface $statement,
        private readonly QueryExecutionTimeLogger $timeLogger,
        private readonly string $sql,
    ) {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->params[$param] = $value;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $name = "{$this->timeLogger->getSequenceId()}. {$this->sql}";

        // 将参数转换为字符串键数组以匹配 watch() 方法的期望类型
        $stringKeyParams = [];
        foreach ($this->params as $key => $value) {
            $stringKeyParams[(string) $key] = $value;
        }

        return $this->timeLogger->watch($name, $this->sql, $stringKeyParams, function () {
            return parent::execute();
        });
    }
}
