<?php

declare(strict_types=1);

namespace Tourze\DoctrineLoggerBundle\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Doctrine\Deprecations\Deprecation;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class LogStatement extends AbstractStatementMiddleware
{
    /** @var array<int,mixed>|array<string,mixed> */
    private array $params = [];

    /** @var array<int,int>|array<string,int> */
    private array $types = [];

    /** @internal This statement can be only instantiated by its connection. */
    public function __construct(
        StatementInterface $statement,
        private readonly QueryExecutionTimeLogger $timeLogger,
        private readonly string $sql,
    ) {
        parent::__construct($statement);
    }

    public function bindValue($param, $value, $type = ParameterType::STRING): void
    {
        if (\func_num_args() < 3) {
            Deprecation::trigger(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/5558',
                'Not passing $type to Statement::bindValue() is deprecated.'
                    . ' Pass the type corresponding to the parameter being bound.',
            );
        }

        $this->params[$param] = $value;
        $this->types[$param]  = $type;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $name = "{$this->timeLogger->getSequenceId()}. {$this->sql}";
        return $this->timeLogger->watch($name, $this->sql, $params ?? $this->params, function () {
            return parent::execute();
        });
    }
}
