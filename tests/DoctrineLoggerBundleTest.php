<?php

declare(strict_types=1);

namespace Tourze\DoctrineLoggerBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineLoggerBundle\DoctrineLoggerBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineLoggerBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineLoggerBundleTest extends AbstractBundleTestCase
{
}
