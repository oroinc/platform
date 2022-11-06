<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Configuration;

use Oro\Bundle\SecurityBundle\Configuration\ConfigurablePermissionConfigurationProvider;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\SecurityBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ConfigurablePermissionConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /**
     * @dataProvider bundlesProvider
     */
    public function testGetConfiguration(array $bundles, array $expected)
    {
        $resourceBundles = [];
        /** @var Bundle $bundle */
        foreach ($bundles as $bundle) {
            $resourceBundles[$bundle->getName()] = get_class($bundle);
        }

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($resourceBundles);

        $provider = new ConfigurablePermissionConfigurationProvider(
            $this->getTempFile('ConfigurablePermissionConfigurationProvider'),
            false,
        );

        $this->assertEquals($expected, $provider->getConfiguration());
    }

    public function bundlesProvider(): array
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        return [
            'first bundle' => [
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
            ],
            'second bundle' => [
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
            ]
        ];
    }
}
