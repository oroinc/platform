<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionListConfiguration;
use Oro\Bundle\SecurityBundle\Tests\Unit\Configuration\Stub\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Configuration\Stub\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ConfigurablePermissionConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider bundlesProvider
     *
     * @param Bundle[] $bundles
     * @param array $expected
     */
    public function testGetConfiguration(array $bundles, array $expected)
    {
        $resourceBundles = [];
        foreach ($bundles as $bundle) {
            $resourceBundles[$bundle->getName()] = get_class($bundle);
        }

        $provider = $this->createConfigurationProvider($resourceBundles);
        CumulativeResourceManager::getInstance()->clear()->setBundles($resourceBundles);

        $this->assertEquals($expected, $provider->getConfiguration());
    }

    /**
     * @return \Generator
     */
    public function bundlesProvider()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        yield 'first bundle' => [
            'bundles' => [$bundle1],
            'expected' => [
                'commerce' => [
                    'default' => true,
                    'entities' => [
                        'TestEntity' => [
                            'UPDATE' => true
                        ]
                    ],
                    'workflows' => [
                        'TestWorkflow' => [
                            'TRANSIT' => false
                        ]
                    ],
                    'capabilities' => [
                        'action1' => true
                    ]
                ]
            ],
        ];

        yield 'second bundle' => [
            'bundles' => [$bundle1, $bundle2],
            'expected' => [
                'commerce' => [
                    'default' => true,
                    'entities' => [
                        'TestEntity' => [
                            'UPDATE' => false,
                            'DELETE' => true,
                        ]
                    ],
                    'workflows' => [
                        'TestWorkflow' => [
                            'VIEW' => false,
                            'DELETE' => true,
                            'TRANSIT' => false,
                        ]
                    ],
                    'capabilities' => [
                        'action1' => false,
                        'action2' => true
                    ]
                ]
            ],
        ];
    }

    /**
     * @param array $bundles
     * @return ConfigurablePermissionConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createConfigurationProvider(array $bundles)
    {
        $provider = $this->getMockBuilder(ConfigurablePermissionConfigurationProvider::class)
            ->setConstructorArgs([new ConfigurablePermissionListConfiguration(), $bundles])
            ->setMethods(['getConfigPath'])
            ->getMock();

        $provider->expects($this->any())->method('getConfigPath')->willReturn('configurable_permissions.yml');

        return $provider;
    }
}
