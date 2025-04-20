# Doctrine Logger Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-logger-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-logger-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/doctrine-logger-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-logger-bundle)

A Symfony bundle that provides enhanced logging capabilities for Doctrine ORM queries, with a focus on performance monitoring and debugging.

## Features

- Monitors SQL query execution time
- Logs queries that exceed configurable time thresholds
- Provides detailed backtrace information for slow queries
- Normalizes and truncates query parameters for better log readability
- Integrates with Symfony's Stopwatch component for precise timing
- Compatible with PSR-3 loggers

## Installation

```bash
composer require tourze/doctrine-logger-bundle
```

## Configuration

The bundle works with minimal configuration. After installation, add it to your bundles in `config/bundles.php`:

```php
<?php

return [
    // ...
    Tourze\DoctrineLoggerBundle\DoctrineLoggerBundle::class => ['all' => true],
];
```

### Environment Variables

You can configure the bundle using the following environment variables:

- `SQL_LOG_LENGTH`: Maximum length of SQL queries in logs (default: 1000)
- `LOG_DB_QUERY_BACKTRACE`: Enable backtrace logging for all queries (default: false)

## Usage

The bundle automatically registers the `QueryExecutionTimeLogger` service which monitors Doctrine queries. By default, it logs:

- All queries in development environment
- Only slow queries (exceeding threshold) in production environment

### Basic Example

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
                // Execute your query here
                // For example:
                // return $this->connection->executeQuery($sql, $params)->fetchAllAssociative();
            }
        );
    }
}
```

## How It Works

The bundle uses Symfony's Stopwatch component to measure query execution time. When a query exceeds the configured threshold (default: 1000ms), it logs detailed information including:

- The SQL query
- Query parameters (normalized and truncated for readability)
- Execution time
- Backtrace information (if enabled)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This bundle is available under the MIT license. See the [LICENSE](LICENSE) file for more information.
