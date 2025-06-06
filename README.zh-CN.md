# Doctrine Logger Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-logger-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-logger-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-logger-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-logger-bundle)

一个为 Doctrine ORM 查询提供增强日志功能的 Symfony Bundle，专注于性能监控和调试。

## 功能特性

- 监控 SQL 查询执行时间
- 记录超过可配置时间阈值的查询
- 为慢查询提供详细的堆栈跟踪信息
- 规范化和截断查询参数，提高日志可读性
- 集成 Symfony 的 Stopwatch 组件，实现精确计时
- 兼容 PSR-3 日志记录器

## 安装

```bash
composer require tourze/doctrine-logger-bundle
```

## 配置

该 Bundle 只需最少的配置即可工作。安装后，将其添加到 `config/bundles.php` 中的 bundles 列表：

```php
<?php

return [
    // ...
    Tourze\DoctrineLoggerBundle\DoctrineLoggerBundle::class => ['all' => true],
];
```

### 环境变量

您可以使用以下环境变量配置此 Bundle：

- `SQL_LOG_LENGTH`：日志中 SQL 查询的最大长度（默认值：1000）
- `LOG_DB_QUERY_BACKTRACE`：为所有查询启用堆栈跟踪日志记录（默认值：false）

## 使用方法

该 Bundle 自动注册 `QueryExecutionTimeLogger` 服务，用于监控 Doctrine 查询。默认情况下，它会记录：

- 开发环境中的所有查询
- 生产环境中仅记录慢查询（超过阈值）

### 基本示例

```php
<?php

use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class MyService
{
    public function __construct(
        private QueryExecutionTimeLogger $queryLogger
    ) {}

    public function executeCustomQuery(string $sql, array $params): mixed
    {
        return $this->queryLogger->watch(
            'my_custom_query',
            $sql,
            $params,
            function() use ($sql, $params) {
                // 在这里执行您的查询
                // 例如：
                // return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
            }
        );
    }
}
```

## 工作原理

该 Bundle 使用 Symfony 的 Stopwatch 组件来测量查询执行时间。当查询超过配置的阈值（默认：1000ms）时，它会记录详细信息，包括：

- SQL 查询语句
- 查询参数（经过规范化和截断，提高可读性）
- 执行时间
- 堆栈跟踪信息（如果启用）

## 贡献

欢迎贡献！请随时提交 Pull Request。

## 许可证

此 Bundle 基于 MIT 许可证提供。有关更多信息，请参阅 [LICENSE](LICENSE) 文件。
