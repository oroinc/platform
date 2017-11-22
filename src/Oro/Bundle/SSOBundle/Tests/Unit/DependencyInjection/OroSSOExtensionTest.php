<?php

namespace Oro\Bundle\SSOBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SSOBundle\DependencyInjection\OroSSOExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroSSOExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroSSOExtension());

        $expectedDefinitions = [
            'oro_sso.oauth_provider',
            'oro_sso.event_listener.user_email_change_listener',
            'oro_sso.token.factory.oauth',
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testAddPrefix()
    {
        $backendPrefix = '/admin';
        $originalConfig = [
            0 =>[
                'firewalls' => [
                    'main' => [
                        'oauth' => [
                            'resource_owners' => [
                                'google' => '/login/check-google',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expectedConfig = [
            0 =>[
                'firewalls' => [
                    'main' => [
                        'oauth' => [
                            'resource_owners' => [
                                'google' => $backendPrefix . '/login/check-google',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $containerBuilder = new ExtendedContainerBuilder();
        $containerBuilder->setParameter('web_backend_prefix', $backendPrefix);
        $containerBuilder->setExtensionConfig('security', $originalConfig);

        $ssoExtension = new OroSSOExtension();
        $prependPrefix = new \ReflectionMethod(
            OroSSOExtension::class,
            'prepend'
        );
        $prependPrefix->invoke($ssoExtension, $containerBuilder);

        $this->assertEquals($expectedConfig, $containerBuilder->getExtensionConfig('security'));
    }
}
