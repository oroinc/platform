<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Component\Testing\ReflectionUtil;

class OroPlatformExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testSecurityShouldBeMergedCorrectly()
    {
        $originalConfig = [
            [
                'access_decision_manager' => [
                    'strategy' => 'unanimous',
                ],
                'firewalls' => [
                    'dev' => [
                        'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                        'security' => false,
                    ],
                    'main' => [
                        'pattern' => '^/',
                        'provider' => 'chain_provider',
                        'organization-form-login' => [
                            'csrf_token_generator' => 'security.csrf.token_manager',
                            'check_path' => 'oro_user_security_check',
                            'login_path' => 'oro_user_security_login',
                        ],
                        'logout' => [
                            'path' => 'oro_user_security_logout',
                        ],
                        'organization-remember-me' => [
                            'key' => '%kernel.secret%',
                            'name' => 'CRMRM',
                            'lifetime' => 1209600,
                            'httponly' => true,
                        ],
                        'anonymous' => false,
                    ],
                ],
            ],
            [
                'firewalls' => [
                    'main' => [
                        'organization-http-basic' => [
                            'realm' => 'Secured REST Area',
                        ],
                        'provider' => 'oro_user',
                        'http-basic' => false,
                        'organization-form-login' => false,
                        'logout' => false,
                        'organization-remember-me' => false,
                        'anonymous' => true,
                    ],
                ],
                'acl' => [
                    'connection' => 'default',
                ],
            ],
        ];

        $additionalConfig = [
            'firewalls' => [
                'oauth' => [
                    'resource_owners' => [
                        'google' => '/login/check-google',
                    ],
                ],
            ],
        ];

        $expectedConfig = $originalConfig;
        $expectedConfig[0]['firewalls']['oauth'] = $additionalConfig['firewalls']['oauth'];

        $containerBuilder = new ExtendedContainerBuilder();
        $containerBuilder->setExtensionConfig('security', $originalConfig);

        $platformExtension = new OroPlatformExtension();
        ReflectionUtil::callMethod(
            $platformExtension,
            'mergeConfigIntoOne',
            [$containerBuilder, 'security', $additionalConfig]
        );

        $this->assertEquals($expectedConfig, $containerBuilder->getExtensionConfig('security'));
    }
}
