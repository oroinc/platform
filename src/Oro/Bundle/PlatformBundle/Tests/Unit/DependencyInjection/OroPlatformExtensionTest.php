<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension;
use Oro\Bundle\SecurityBundle\DependencyInjection\OroSecurityExtension;
use Oro\Component\Config\CumulativeResourceManager;
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

    public function testThatWebBackendPrefixIsUsedDefaultWhenParamNotPassed()
    {
        $extension = new OroPlatformExtension();

        $containerBuilder = $this->createMock(ExtendedContainerBuilder::class);

        $containerBuilder
            ->expects($this->any())
            ->method('getExtensionConfig')
            ->willReturn([]);

        $extension->prepend($containerBuilder);
    }

    public function testThatWebBackendPrefixIsNotUsedDefaultWhenParameterPassed()
    {
        $extension = new OroPlatformExtension();

        $containerBuilder = $this->createMock(ExtendedContainerBuilder::class);

        $containerBuilder
            ->expects($this->any())
            ->method('getExtensionConfig')
            ->willReturn([]);

        $extension->prepend($containerBuilder);
    }

    public function testAccessControlSorting()
    {
        $extension = new OroPlatformExtension();
        $fooBundle = new Fixtures\FooBundle\FooBundle();
        $barBundle = new Fixtures\BarBundle\BarBundle();
        $fooBarBundle = new Fixtures\FooBarBundle\FooBarBundle();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $fooBundle->getName() => get_class($fooBundle),
                $barBundle->getName() => get_class($barBundle),
                $fooBarBundle->getName() => get_class($fooBarBundle)
            ]);

        $containerBuilder = $this->createMock(ExtendedContainerBuilder::class);
        $containerBuilder->method('getExtensions')->willReturn([
            'oro_security' => $this->createMock(OroSecurityExtension::class)
        ]);

        $containerBuilder->method('hasExtension')->willReturnMap([
            ['security', false],
            ['oro_security', true],
            ['jms_serializer', false],
        ]);

        $appLevelConfig = [
            [
                'access_control' => [
                    [
                        'path' => '^/test-us$',
                        'roles' => 'APP_LEVEL_CONFIG_ROLE_130',
                        'priority' => 130,
                    ],
                ],
            ]
        ];
        $containerBuilder->method('getExtensionConfig')->willReturnMap(
            [
                ['oro_security', $appLevelConfig],
                ['security', []],
                ['doctrine', []],
            ]
        );

        $containerBuilder->method('setExtensionConfig')->with('security', $this->getExpectedPrioritizedRules());
        $extension->prepend($containerBuilder);
    }

    private function getExpectedPrioritizedRules(): array
    {
        return [
            [
                'access_control' => [
                    [
                        'path' => '^/test-us$',
                        'roles' => 'APP_LEVEL_CONFIG_ROLE_130',
                    ],
                    [
                        'path' => '^/test-us$',
                        'roles' => 'FOO_ROLE_110',
                    ],
                    [
                        'path' => '^/test-us$',
                        'roles' => 'BAR_ROLE_50',
                    ],
                    [
                        'path' => '^/contact-us$',
                        'roles' => 'FOO_BAR_ROLE_20',
                    ],
                    [
                        'path' => '^/contact-us$',
                        'roles' => 'FOO_ROLE_0',
                    ],
                    [
                        'path' => '^/test-us/test$',
                        'roles' => 'FOO_ROLE_0',
                    ],
                    [
                        'path' => '^/test-us/test$',
                        'roles' => 'FOO_BAR_ROLE_0',
                    ],
                    [
                        'path' => '^/test-us/test/us$',
                        'roles' => 'FOO_BAR_ROLE_0',
                    ],
                    [
                        'path' => '^/sort-us$',
                        'roles' => 'FOO_BAR_ROLE_0',
                    ],
                    [
                        'path' => '^/test-us$',
                        'ip' => '127.0.0.1',
                        'roles' => 'FOO_BAR_ROLE_-10',
                    ],
                    [
                        'path' => '^/contact-us$',
                        'ip' => '127.0.0.1',
                        'roles' => 'BAR_ROLE_-100',
                    ],
                ],
            ],
        ];
    }
}
