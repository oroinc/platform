<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\UserBundle\DependencyInjection\OroUserExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroUserExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoadWithDefaults()
    {
        $config = [];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUserExtension();
        $extension->load([$config], $container);

        $this->assertEquals(86400, $container->getParameter('oro_user.reset.ttl'));
    }

    public function testLoad()
    {
        $config = [
            'reset' => [
                'ttl' => 1800
            ]
        ];

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroUserExtension();
        $extension->load([$config], $container);

        $this->assertEquals(1800, $container->getParameter('oro_user.reset.ttl'));
    }

    public function testPrepend()
    {
        $inputSecurityConfig = [
            'firewalls' => [
                'main' => ['main_config'],
                'first' => ['first_config'],
                'second' => ['second_config'],
            ]
        ];
        $expectedSecurityConfig = [
            'firewalls' => [
                'first' => ['first_config'],
                'second' => ['second_config'],
                'main' => ['main_config'],
            ]
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject|ExtendedContainerBuilder $container */
        $container = $this->createMock(ExtendedContainerBuilder::class);
        $container->expects($this->once())
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturn([$inputSecurityConfig]);
        $container->expects($this->once())
            ->method('setExtensionConfig')
            ->with('security', [$expectedSecurityConfig]);

        $extension = new OroUserExtension();
        $extension->prepend($container);
    }
}
