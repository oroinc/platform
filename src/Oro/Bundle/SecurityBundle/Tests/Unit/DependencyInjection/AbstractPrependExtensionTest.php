<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

abstract class AbstractPrependExtensionTest extends ExtensionTestCase
{
    /**
     * @return PrependExtensionInterface
     */
    abstract protected function getExtension();

    public function testPrepend()
    {
        $securityConfig = [
            0 => [
                'firewalls' => [
                    'frontend_secure' => ['frontend_secure_config'],
                    'frontend' => ['frontend_config'],
                    'main' => ['main_config'],
                ]
            ]
        ];
        $expectedSecurityConfig = [
            0 => [
                'firewalls' => [
                    'main' => ['main_config'],
                    'frontend_secure' => ['frontend_secure_config'],
                    'frontend' => ['frontend_config'],
                ]
            ]
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject|ExtendedContainerBuilder $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ExtendedContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->exactly(2))
            ->method('getExtensionConfig')
            ->with('security')
            ->willReturnCallback(
                function () use (&$securityConfig) {
                    return $securityConfig;
                }
            );
        $containerBuilder->expects($this->exactly(2))
            ->method('setExtensionConfig')
            ->with('security', $this->isType('array'))
            ->willReturnCallback(
                function ($name, array $config = []) use (&$securityConfig) {
                    $securityConfig = $config;
                }
            );

        $extension = $this->getExtension();
        $extension->prepend($containerBuilder);
        $this->assertEquals($expectedSecurityConfig, $securityConfig);
    }
}
