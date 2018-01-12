<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ImapBundle\DependencyInjection\Compiler\CredentialsNotificationSenderPass;

class CredentialsNotificationSenderPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessNoMainService()
    {
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_imap.origin_credentials.issue_manager')
            ->willReturn(false);

        $containerBuilder->expects($this->never())
            ->method('getDefinition');
        $containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds');

        $pass = new CredentialsNotificationSenderPass();
        $pass->process($containerBuilder);
    }

    public function testProcess()
    {
        $definition = new Definition();

        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_imap.origin_credentials.issue_manager')
            ->willReturn(true);

        $containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_imap.origin_credentials.issue_manager')
            ->willReturn($definition);

        $containerBuilder->expects($this->exactly(2))
            ->method('findTaggedServiceIds')
            ->willReturnMap(
                [
                    [
                        'oro_imap.origin_credentials.notification_sender',
                        ['first_service' => [], 'second_service' => []]
                    ],
                    [
                        'oro_imap.origin_credentials.user_notification_sender',
                        ['first_user_service' => []]
                    ],
                ]
            );

        $pass = new CredentialsNotificationSenderPass();
        $pass->process($containerBuilder);

        $calls = $definition->getMethodCalls();
        $this->assertCount(3, $calls);

        $this->assertEquals('addNotificationSender', $calls[0][0]);
        $this->assertEquals([new Reference('first_service')], $calls[0][1]);

        $this->assertEquals('addNotificationSender', $calls[1][0]);
        $this->assertEquals([new Reference('second_service')], $calls[1][1]);

        $this->assertEquals('addUserNotificationSender', $calls[2][0]);
        $this->assertEquals([new Reference('first_user_service')], $calls[2][1]);
    }
}
