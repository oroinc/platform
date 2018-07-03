<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Parser;

use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Oro\Bundle\PlatformBundle\DependencyInjection\OroPlatformExtension;

class OroPlatformExtensionTest extends \PHPUnit_Framework_TestCase
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

    public function testDbalConfigWithoutCommonConfig()
    {
        $doctrineConfig = [
            [
                'dbal' => [
                    'default_connection' => 'default',
                    'connections'        => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ],
                        'another' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ]
                    ]
                ]
            ]
        ];

        $container = new ExtendedContainerBuilder();
        $container->setParameter('database_driver', 'pdo_mysql');
        $container->setExtensionConfig('doctrine', $doctrineConfig);

        $platformExtension = new OroPlatformExtension();
        $platformExtension->prepend($container);

        self::assertEquals($doctrineConfig, $container->getExtensionConfig('doctrine'));
    }

    public function testDbalConfigWithCommonConfig()
    {
        $doctrineConfig = [
            [
                'dbal' => [
                    'default_connection' => 'connection1',
                    'connections'        => [
                        'connection1' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ],
                        'connection2' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ],
                        'connection3' => [
                            'driver' => 'pdo_pgsql',
                            'dbname' => 'test',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ],
                        'connection4' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test1',
                            'host'   => 'localhost',
                            'port'   => '3306'
                        ],
                        'connection5' => [
                            'driver' => 'pdo_mysql',
                            'dbname' => 'test',
                            'host'   => 'localhost'
                        ]
                    ]
                ]
            ],
            [
                'dbal' => [
                    'connections' => [
                        'connection5' => [
                            'port' => '3306'
                        ]
                    ]
                ]
            ],
            [
                'dbal' => [
                    'default_connection'    => 'connection2',
                    'charset'               => 'utf8mb4',
                    'default_table_options' => [
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_unicode_ci'
                    ],
                    'types'                 => [
                        'type1' => 'Type1'
                    ]
                ]
            ]
        ];

        $container = new ExtendedContainerBuilder();
        $container->setParameter('database_driver', 'pdo_mysql');
        $container->setExtensionConfig('doctrine', $doctrineConfig);

        $platformExtension = new OroPlatformExtension();
        $platformExtension->prepend($container);

        $expectedDoctrineConfig = [
            $doctrineConfig[0],
            $doctrineConfig[1],
            [
                'dbal' => [
                    'connections' => [
                        'connection1' => [
                            'charset'               => 'utf8mb4',
                            'default_table_options' => [
                                'charset' => 'utf8mb4',
                                'collate' => 'utf8mb4_unicode_ci'
                            ]
                        ],
                        'connection5' => [
                            'charset'               => 'utf8mb4',
                            'default_table_options' => [
                                'charset' => 'utf8mb4',
                                'collate' => 'utf8mb4_unicode_ci'
                            ]
                        ]
                    ]
                ]
            ],
            $doctrineConfig[2]
        ];
        self::assertEquals($expectedDoctrineConfig, $container->getExtensionConfig('doctrine'));
    }
}
