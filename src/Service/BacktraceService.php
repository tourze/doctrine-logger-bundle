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
        return Backtrace::create()->toString();
    }
}
