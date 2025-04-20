# Doctrine Logger Bundle Workflow

This document describes the workflow and data flow of the Doctrine Logger Bundle.

## Query Execution Monitoring Workflow

```mermaid
flowchart TD
    A[Doctrine Query Execution] -->|Intercepted by| B[QueryExecutionTimeLogger];
    B -->|Start| C[Symfony Stopwatch];
    B -->|Execute| D[Original Query];
    D -->|Return| E[Query Result];
    C -->|Stop| F[Calculate Duration];
    F -->|Check| G{Duration > Threshold?};
    G -->|Yes| H[Log Error with Backtrace];
    G -->|No| I{Is Production?};
    I -->|Yes| J{Is SELECT 1?};
    J -->|No| K[Log Info];
    I -->|No| L[Log All Queries];

    subgraph Normalization
    M[Normalize Parameters] --> N[Truncate Long Strings];
    N --> O[Handle Binary Data];
    end

    B -->|If params exist| M;
    O -->|Provide to| K;
    O -->|Provide to| H;
```

## Component Interaction

```mermaid
sequenceDiagram
    participant App as Application Code
    participant Logger as QueryExecutionTimeLogger
    participant SW as Symfony Stopwatch
    participant DB as Database
    participant Log as PSR Logger
    
    App->>Logger: watch(name, sql, params, callback)
    Logger->>SW: start(name)
    Logger->>App: execute callback()
    App->>DB: execute query
    DB-->>App: return result
    App-->>Logger: return result
    Logger->>SW: stop(name)
    SW-->>Logger: return StopwatchEvent
    Logger->>Logger: checkEvent(event, query)
    
    alt duration > threshold
        Logger->>Logger: generateBacktrace()
        Logger->>Log: error('执行SQL时发现可能超时的查询', context)
    else in production & not 'SELECT 1'
        alt LOG_DB_QUERY_BACKTRACE enabled
            Logger->>Logger: generateBacktrace()
        end
        Logger->>Log: info('执行SQL', context)
    end

    Logger-->>App: return original result
```

## Configuration Flow

```mermaid
flowchart LR
    A[Bundle Registration] --> B[DoctrineLoggerExtension];
    B --> C[Load services.yaml];
    C --> D[Register QueryExecutionTimeLogger];
    D --> E[Autowire Dependencies];
    E --> F[Ready for Use];

    G[Environment Variables] --> H{Configure Bundle};
    H --> I[SQL_LOG_LENGTH];
    H --> J[LOG_DB_QUERY_BACKTRACE];
    I --> D;
    J --> D;
```

This workflow documentation illustrates how the Doctrine Logger Bundle intercepts database queries, measures their execution time, and logs appropriate information based on configuration and execution context.
