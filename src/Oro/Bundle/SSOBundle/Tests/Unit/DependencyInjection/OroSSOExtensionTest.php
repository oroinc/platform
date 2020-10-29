<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SSOBundle\DependencyInjection\OroSSOExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroSSOExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testAddBackendPrefix()
    {
        $backendPrefix = '/admin';
        $originalConfig = [
            [
                'firewalls' => [
                    'main' => [
                        'oauth' => [
                            'resource_owners' => [
                                'test_resource_owner' => '/login/check-test-resource-owner'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $expectedConfig = [
            [
                'firewalls' => [
                    'main' => [
                        'oauth' => [
                            'resource_owners' => [
                                'test_resource_owner' => $backendPrefix . '/login/check-test-resource-owner'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $container = new ExtendedContainerBuilder();
        $container->setParameter('web_backend_prefix', $backendPrefix);
        $container->setExtensionConfig('security', $originalConfig);

        $extension = new OroSSOExtension();
        $extension->prepend($container);

        $this->assertEquals($expectedConfig, $container->getExtensionConfig('security'));
    }
}
