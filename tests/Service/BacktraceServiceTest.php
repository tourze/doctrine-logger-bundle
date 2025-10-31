<?php

namespace Tourze\DoctrineLoggerBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineLoggerBundle\Service\BacktraceService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(BacktraceService::class)]
#[RunTestsInSeparateProcesses]
final class BacktraceServiceTest extends AbstractIntegrationTestCase
{
    private BacktraceService $backtraceService;

    protected function onSetUp(): void
    {
        // 无额外设置需求
    }

    private function getBacktraceService(): BacktraceService
    {
        if (!isset($this->backtraceService)) {
            $this->backtraceService = self::getService(BacktraceService::class);
        }

        return $this->backtraceService;
    }

    public function testCreateBacktraceString(): void
    {
        $backtraceString = $this->getBacktraceService()->createBacktraceString();

        $this->assertNotEmpty($backtraceString);

        // 验证backtrace包含当前测试方法的信息
        $this->assertStringContainsString('testCreateBacktraceString', $backtraceString);
    }

    public function testCreateBacktraceStringReturnsConsistentFormat(): void
    {
        $backtrace1 = $this->getBacktraceService()->createBacktraceString();
        $backtrace2 = $this->getBacktraceService()->createBacktraceString();

        // 验证两次调用都返回非空字符串
        $this->assertNotEmpty($backtrace1);
        $this->assertNotEmpty($backtrace2);

        // 验证都包含测试方法信息
        $this->assertStringContainsString('testCreateBacktraceStringReturnsConsistentFormat', $backtrace1);
        $this->assertStringContainsString('testCreateBacktraceStringReturnsConsistentFormat', $backtrace2);
    }
}
