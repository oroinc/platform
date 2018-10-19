<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\AsseticBundle\DependencyInjection\Compiler\AsseticFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AsseticFilterPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('assetic.filter.scssphp')
            ->willReturn(false);

        $container->expects($this->once())
            ->method('removeDefinition')
            ->with('oro_assetic.decorating_filter.scssphp');

        $pass = new AsseticFilterPass();
        $pass->process($container);
    }
}
