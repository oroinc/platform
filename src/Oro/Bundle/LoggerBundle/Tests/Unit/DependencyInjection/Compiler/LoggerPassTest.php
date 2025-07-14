<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\LoggerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LoggerPassTest extends TestCase
{
    public function testAlwaysSetPublicAlias(): void
    {
        $container = new ContainerBuilder();
        $container->setAlias('logger', 'monolog.logger');

        $pass = new LoggerPass();
        $pass->process($container);

        $loggerAlias = $container->getAlias('logger');
        $this->assertTrue($loggerAlias->isPublic());
        $this->assertFalse($loggerAlias->isPrivate());
    }
}
