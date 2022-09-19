<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Configuration;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle3\TestBundle3;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private const DEFAULT_CONFIG_FOR_OPERATION = [
        'routes' => [],
        'replace' => [],
        'applications' => [],
        'groups' => [],
        'for_all_entities' => false,
        'entities' => [],
        'exclude_entities' => [],
        'for_all_datagrids' => false,
        'datagrids' => [],
        'exclude_datagrids' => [],
        'order' => 0,
        'enabled' => true,
        'page_reload' => true,
        'attributes' => [],
        'button_options' => [
            'page_component_options' => [],
            'data' => []
        ],
        'frontend_options' => [
            'options' => [],
            'title_parameters' => [],
            'show_dialog' => true
        ],
        'datagrid_options' => [
            'mass_action' => [],
            'data' => []
        ],
        'preactions' => [],
        'form_init' => [],
        'actions' => [],
        'preconditions' => [],
        'conditions' => []
    ];

    private string $cacheFile;

    /** @var ConfigurationProvider */
    private $configurationProvider;

    protected function setUp(): void
    {
        $container = $this->createMock(Container::class);
        $container->expects($this->any())
            ->method('getParameterBag')
            ->willReturn(new ParameterBag());

        $this->cacheFile = $this->getTempFile('ActionConfigurationProvider');

        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        $bundle3 = new TestBundle3();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
                $bundle3->getName() => get_class($bundle3)
            ]);

        $this->configurationProvider = new ConfigurationProvider(
            $this->cacheFile,
            false,
            $container
        );
    }

    public function testGetConfigurationWithCache()
    {
        $cachedConfig = [
            'operations' => [],
            'action_groups' => []
        ];
        file_put_contents($this->cacheFile, sprintf('<?php return %s;', var_export($cachedConfig, true)));

        $this->assertEquals($cachedConfig, $this->configurationProvider->getConfiguration());
    }

    public function testGetConfigurationWithoutCache()
    {
        $config = $this->configurationProvider->getConfiguration();

        $expectedActionOperations = [
            'test_action1' => array_merge(self::DEFAULT_CONFIG_FOR_OPERATION, [
                'label' => 'Test Action1',
                'routes' => ['test_route_bundle3']
            ]),
            'test_action2' => array_merge(self::DEFAULT_CONFIG_FOR_OPERATION, [
                'label' => 'Test Action2 Bundle3',
                'routes' => ['test_route_bundle3']
            ]),
            'test_action4' => array_merge(self::DEFAULT_CONFIG_FOR_OPERATION, [
                'label' => 'Test Action4',
                'button_options' => [
                    'page_component_options' => [],
                    'data' => [
                        'message' => 'custom value with %percent escaped string% parameter',
                        'custom key with %percent escaped string% parameter' => 'value'
                    ]
                ],
                'datagrid_options' => [
                    'mass_action' => [],
                    'data' => [
                        'sub_config2' => 'data2',
                        'sub_config3' => 'replaced data'
                    ]
                ]
            ]),
            'test_action3' => array_merge(self::DEFAULT_CONFIG_FOR_OPERATION, [
                'label' => 'Test Action2 Bundle3',
                'routes' => ['test_route_bundle3', 'test_route_bundle3_new']
            ])
        ];
        $this->assertEquals($expectedActionOperations, $config['operations']);

        $expectedActionGroups = [
            'group1' => [
                'parameters' => [
                    '$.data' => [
                        'type' => 'Oro\Bundle\TestBundle\Entity\Test',
                        'default' => true
                    ]
                ],
                'conditions' => [
                    '@gt' => ['$updatedAt', '$.date']
                ],
                'actions' => [
                    [
                        '@assign_value' => ['$expired', true]
                    ]
                ]
            ],
            'group2' => [
                'parameters' => [
                    '$.date' => [
                        'type' => 'DateTime',
                        'message' => 'No data specified!'
                    ]
                ],
                'conditions' => [
                    '@gt' => ['$updatedAt', '$.date']
                ],
                'actions' => [
                    [
                        '@assign_value' => ['$expired', true]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expectedActionGroups, $config['action_groups']);
    }
}
