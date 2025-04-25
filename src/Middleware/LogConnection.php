<?php

declare(strict_types=1);

namespace Tourze\DoctrineLoggerBundle\Middleware;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Symfony\Component\Stopwatch\Stopwatch;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogConnection extends AbstractConnectionMiddleware
{
    /**
     * @var array 记录事务长度
     */
    private array $transactionIds = [];

    /**
     * @var array<array{
     *     sql: string,
     *     startTime: float,
     *     endTime: float,
     *     duration: float
     * }> 记录事务内的sql及其执行时间信息
     */
    private array $queries = [];

    public function __construct(
        ConnectionInterface $connection,
        private readonly QueryExecutionTimeLogger $timeLogger,
        private readonly Stopwatch $stopwatch,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): DriverStatement
    {
        if ($this->transactionIds) {
            $startTime = microtime(true);
            $statement = parent::prepare($sql);
            $endTime = microtime(true);

            $this->queries[] = [
                'sql' => $sql,
                'params' => [],  // 初始化空参数数组
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $endTime - $startTime,
            ];

            return new LogStatement(
                $statement,
                $this->timeLogger,
                $sql,
            );
        }

        return new LogStatement(
            parent::prepare($sql),
            $this->timeLogger,
            $sql,
        );
    }

    public function query(string $sql): Result
    {
        if ($this->transactionIds) {
            $startTime = microtime(true);
            $result = parent::query($sql);
            $endTime = microtime(true);

            $this->queries[] = [
                'sql' => $sql,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $endTime - $startTime,
            ];

            return $result;
        }

        $name = "{$this->timeLogger->getSequenceId()}. {$sql}";

        return $this->timeLogger->watch($name, $sql, null, function () use ($sql) {
            return parent::query($sql);
        });
    }

    public function exec(string $sql): int
    {
        if ($this->transactionIds) {
            $startTime = microtime(true);
            $result = parent::exec($sql);
            $endTime = microtime(true);

            $this->queries[] = [
                'sql' => $sql,
                'params' => [],  // 初始化空参数数组
                'startTime' => $startTime,
                'endTime' => $endTime,
                'duration' => $endTime - $startTime,
            ];

            return $result;
        }

        $name = "{$this->timeLogger->getSequenceId()}. {$sql}";

        return $this->timeLogger->watch($name, $sql, null, function () use ($sql) {
            return parent::exec($sql);
        });
    }

    public function beginTransaction(): void
    {
        $id = 'DBAL_PROFILE_TRANSACTION_' . uniqid();
        $this->stopwatch->start($id);
        $this->transactionIds[] = $id;

        parent::beginTransaction();
    }

    public function commit(): void
    {
        try {
            parent::commit();
        } finally {
            $id = array_pop($this->transactionIds);
            $this->timeLogger->checkEvent($this->stopwatch->stop($id), ['transactionId' => $id], $this->queries);
            $this->queries = [];
        }
    }

    public function rollBack(): void
    {
        try {
            parent::rollBack();
        } finally {
            $id = array_pop($this->transactionIds);
            $this->timeLogger->checkEvent($this->stopwatch->stop($id), ['transactionId' => $id]);
            $this->queries = [];
        }
    }
}
