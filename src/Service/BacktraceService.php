<?php

namespace Tourze\DoctrineLoggerBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\BacktraceHelper\Backtrace;

/**
 * 提供调用堆栈追踪功能的服务
 */
#[Autoconfigure(public: true)]
class BacktraceService
{
    public function createBacktraceString(): string
    {
        // @phpstan-ignore-next-line 跨模块调用合理：BacktraceHelper是工具包，没有Service层
        return Backtrace::create()->toString();
    }
}
