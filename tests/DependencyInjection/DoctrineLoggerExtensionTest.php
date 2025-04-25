<?php

namespace Tourze\DoctrineLoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\DoctrineLoggerBundle\DependencyInjection\DoctrineLoggerExtension;
use Tourze\DoctrineLoggerBundle\Middleware\LogMiddleware;
use Tourze\DoctrineLoggerBundle\Service\QueryExecutionTimeLogger;

class DoctrineLoggerExtensionTest extends TestCase
{
    private DoctrineLoggerExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new DoctrineLoggerExtension();
        $this->container = new ContainerBuilder();
    }

    public function testLoad(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务是否已注册
        $this->assertTrue($this->container->hasDefinition(QueryExecutionTimeLogger::class));
        $this->assertTrue($this->container->hasDefinition(LogMiddleware::class));

        $serviceDefinition = $this->container->getDefinition(QueryExecutionTimeLogger::class);
        $this->assertTrue($serviceDefinition->isAutowired());
        $this->assertTrue($serviceDefinition->isAutoconfigured());
    }
}
