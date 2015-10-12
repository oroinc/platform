<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension;

class OroCronExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $configuration = new ContainerBuilder();

        $loader = new OroPlatformExtension();
        $config = $this->getEmptyConfig();

        $loader->load(array($config), $configuration);

        $this->assertTrue($configuration instanceof ContainerBuilder);
    }

    /**
     * @return array
     */
    protected function getEmptyConfig()
    {
        $yaml   = '';
        $parser = new Parser();

        return $parser->parse($yaml);
    }

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
                            'csrf_provider' => 'form.csrf_provider',
                            'check_path' => 'oro_user_security_check',
                            'login_path' => 'oro_user_security_login',
                        ],
                        'logout' => [
                            'path' => 'oro_user_security_logout',
                        ],
                        'organization-remember-me' => [
                            'key' => '%secret%',
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
        $mergeConfigurationIntoOne = new \ReflectionMethod(
            'Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension',
            'mergeConfigIntoOne'
        );
        $mergeConfigurationIntoOne->setAccessible(true);

        $mergeConfigurationIntoOne->invoke($platformExtension, $containerBuilder, 'security', $additionalConfig);

        $this->assertEquals($expectedConfig, $containerBuilder->getExtensionConfig('security'));
    }
}
