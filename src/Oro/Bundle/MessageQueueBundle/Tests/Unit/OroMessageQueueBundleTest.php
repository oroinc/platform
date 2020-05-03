<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\Transport\Factory\DbalTransportFactory;
use Oro\Bundle\MessageQueueBundle\OroMessageQueueBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroMessageQueueBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild(): void
    {
        /** @var OroMessageQueueExtension|\PHPUnit\Framework\MockObject\MockObject $extension */
        $extension = $this->createMock(OroMessageQueueExtension::class);
        $extension->expects($this->once())
            ->method('getAlias')
            ->willReturn('oro_message_queue');
        $extension->expects($this->once())
            ->method('addTransportFactory')
            ->with(new DbalTransportFactory());

        $container = new ContainerBuilder();
        $container->registerExtension($extension);
        $bundle = new OroMessageQueueBundle();
        $bundle->build($container);
    }
}
