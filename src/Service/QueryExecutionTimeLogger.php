<?php

namespace Tourze\DoctrineLoggerBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Logger\DbalLogger;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Symfony\Contracts\Service\ResetInterface;
use Tourze\BacktraceHelper\Backtrace;
use Yiisoft\Strings\StringHelper;

/**
 * 统计SQL执行效率
 */
#[AutoconfigureTag('as-coroutine')]
class QueryExecutionTimeLogger implements ResetInterface
{
    public const MAX_STRING_LENGTH = 32;

    public const BINARY_DATA_VALUE = '(binary value)';

    public const DEFAULT_MAX_EXECUTION_TIME_THRESHOLD = 1000;

    private bool $enableBacktrace;

    private int $executionTimeThreshold;

    private static int $sequenceNumber = 0;

    public function __construct(
        private readonly LoggerInterface $sqlLogger,
        private readonly Stopwatch $stopwatch,
    )
    {
        $this->enableBacktrace = true;
        $this->executionTimeThreshold = self::DEFAULT_MAX_EXECUTION_TIME_THRESHOLD;
    }

    public function getSequenceId(): string
    {
        self::$sequenceNumber++;
        return (string) self::$sequenceNumber;
    }

    public function watch(string $name, string $sql, array|null $params, callable $callback): mixed
    {
        $this->stopwatch->start($name);
        $currentQuery = [
            'sql' => $sql,
            'params' => null === $params ? [] : $this->normalizeParams($params),
        ];

        try {
            return $callback();
        } finally {
            $event = $this->stopwatch->stop($name);
            $this->checkEvent($event, $currentQuery);
        }
    }

    public function checkEvent(StopwatchEvent $event, array $currentQuery = [], array $subQueries = []): void
    {
        $duration = $event->getDuration();
        $context = [
            'executionTime' => $duration,
            ...$currentQuery,
        ];
        if (isset($context['sql'])) {
            $context['sql'] = StringHelper::truncateMiddle($context['sql'], intval($_ENV['SQL_LOG_LENGTH'] ?? 1000));
        }

        if ($duration < $this->executionTimeThreshold) {
            // 消息队列相关的，我们不打印日志，要不太多了
            if ('prod' === $_ENV['APP_ENV'] && 'SELECT 1' !== ($currentQuery['sql'] ?? '')) {
                if ($_ENV['LOG_DB_QUERY_BACKTRACE'] ?? false) {
                    $context += ['backtrace' => Backtrace::create()->toString()];
                }
                $this->sqlLogger->info('执行SQL', $context);
            }

            return;
        }

        if ($this->enableBacktrace) {
            $context += ['backtrace' => Backtrace::create()->toString()];
        }

        $this->sqlLogger->error('执行SQL时发现可能超时的查询', $context);
        foreach ($subQueries as $subQuery) {
            $this->sqlLogger->warning('记录超时查询发生时的子查询', $subQuery);
        }
    }

    /**
     * @see DbalLogger::normalizeParams()
     */
    protected function normalizeParams(array $params): array
    {
        foreach ($params as $index => $param) {
            // normalize recursively
            if (\is_array($param)) {
                $params[$index] = $this->normalizeParams($param);
                continue;
            }

            if (!\is_string($param)) {
                continue;
            }

            // non utf-8 strings break json encoding
            if (!preg_match('//u', $param)) {
                $params[$index] = self::BINARY_DATA_VALUE;
                continue;
            }

            // detect if the string is too long, and must be shortened
            if (self::MAX_STRING_LENGTH < mb_strlen($param, 'UTF-8')) {
                $params[$index] = mb_substr($param, 0, self::MAX_STRING_LENGTH - 6, 'UTF-8') . ' [...]';
                continue;
            }
        }

        return $params;
    }

    public function reset(): void
    {
        $this->stopwatch->reset();
    }
}
