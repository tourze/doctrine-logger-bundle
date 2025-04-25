<?php

namespace Tourze\DoctrineLoggerBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\BacktraceHelper\Backtrace;
use Tourze\DoctrineLoggerBundle\DoctrineLoggerBundle;

class DoctrineLoggerBundleTest extends TestCase
{
    private DoctrineLoggerBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new DoctrineLoggerBundle();
    }

    public function testBoot(): void
    {
        // 在运行 boot 之前记录当前忽略文件的数量
        $reflectionClass = new \ReflectionClass(Backtrace::class);
        $prodIgnoreFilesProperty = $reflectionClass->getProperty('prodIgnoreFiles');
        $prodIgnoreFilesProperty->setAccessible(true);

        $ignoreFilesBefore = count($prodIgnoreFilesProperty->getValue());

        // 运行 boot 方法
        $this->bundle->boot();

        // 验证是否添加了 3 个新的忽略文件
        $ignoreFilesAfter = count($prodIgnoreFilesProperty->getValue());
        $this->assertEquals($ignoreFilesBefore + 3, $ignoreFilesAfter);
    }
}
