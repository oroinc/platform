<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtension;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationExtensionInterface;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class FeatureToggleConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private FeatureToggleConfiguration $configuration;

    protected function setUp(): void
    {
        $extension = $this->createMock(ConfigurationExtensionInterface::class);
        $extension->expects(self::any())
            ->method('extendConfigurationTree')
            ->willReturnCallback(function (NodeBuilder $node) {
                $node->arrayNode('test_items')->prototype('variable')->end()->end();
            });

        $this->configuration = new FeatureToggleConfiguration(new ConfigurationExtension([$extension]));
    }

    private function processConfiguration(array $inputData): array
    {
        return (new Processor())->processConfiguration($this->configuration, [$inputData]);
    }

    public function testProcessEmptyConfiguration(): void
    {
        $this->assertEquals([], $this->processConfiguration([]));
    }

    public function testProcessMinValidConfiguration(): void
    {
        $inputData = [
            'feature1' => [
                'label' => 'Feature 1 Label'
            ],
        ];

        $expected = [
            'feature1' => [
                'label' => 'Feature 1 Label',
                'dependencies' => [],
                'routes' => [],
                'configuration' => [],
                'entities' => [],
                'commands' => [],
                'mq_topics' => [],
                'test_items' => []
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($inputData));
    }

    public function testProcessFullValidConfiguration(): void
    {
        $inputData = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'dependencies' => ['feature_one', 'feature_two'],
                'routes' => ['oro_feature_route'],
                'configuration' => ['oro_feature', 'oro_another'],
                'entities' => [],
                'strategy' => 'affirmative',
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' => true,
                'mq_topics' => ['mq.topic1', 'mq.topic2'],
                'test_items' => ['item1', 'item2']
            ],
        ];

        $expected = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'dependencies' => ['feature_one', 'feature_two'],
                'routes' => ['oro_feature_route'],
                'configuration' => ['oro_feature', 'oro_another'],
                'entities' => [],
                'strategy' => 'affirmative',
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' => true,
                'commands' => [],
                'mq_topics' => ['mq.topic1', 'mq.topic2'],
                'test_items' => ['item1', 'item2']
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($inputData));
    }

    /**
     * @dataProvider processInvalidConfigurationProvider
     */
    public function testProcessInvalidConfiguration(array $inputData, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->processConfiguration($inputData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processInvalidConfigurationProvider(): array
    {
        return [
            'incorrect root' => [
                'input' => [
                    'feature1' => 'not array value'
                ],
                'message' => 'Invalid type for path "features.feature1". Expected "array", but got "string"'
            ],
            'incorrect label' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                    ]
                ],
                'message' => 'The child config "label" under "features.feature1" must be configured'
            ],
            'incorrect description' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'description' => ['array']
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.description". ' .
                    'Expected "scalar", but got "array"'
            ],
            'incorrect dependencies' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'dependencies' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.dependencies". ' .
                    'Expected "array", but got "string"'
            ],
            'incorrect route' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'routes' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.routes". ' .
                    'Expected "array", but got "string"'
            ],
            'incorrect configuration' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'configuration' => 42
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.configuration". ' .
                    'Expected "array", but got "int"'
            ],
            'incorrect strategy' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'strategy' => 'not supported'
                    ]
                ],
                'message' => 'The value "not supported" is not allowed for path "features.feature1.strategy". ' .
                    'Permissible values: "unanimous", "affirmative", "consensus"'
            ],
            'incorrect allow_if_all_abstain' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'allow_if_all_abstain' => 'not_bool'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.allow_if_all_abstain". ' .
                             'Expected "bool", but got "string"'
            ],
            'incorrect allow_if_equal_granted_denied' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'allow_if_equal_granted_denied' => 'not_bool'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.allow_if_equal_granted_denied". ' .
                             'Expected "bool", but got "string"'
            ],
        ];
    }
}
