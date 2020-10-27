<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;
use Symfony\Component\Config\Definition\Processor;

class FeatureToggleConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FeatureToggleConfiguration
     */
    protected $configuration;

    protected function setUp(): void
    {
        $this->configuration = new FeatureToggleConfiguration();
    }

    public function testProcessEmptyConfiguration()
    {
        $this->assertEquals([], $this->processConfiguration([]));
    }

    public function testProcessMinValidConfiguration()
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
                'field_configs' => [],
                'commands' => [],
                'sidebar_widgets' => [],
                'dashboard_widgets' => [],
                'cron_jobs' => [],
                'api_resources' => [],
                'navigation_items' => [],
                'operations' => [],
                'workflows' => [],
                'processes' => [],
                'placeholder_items' => [],
                'mq_topics' => []
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($inputData));
    }

    public function testProcessFullValidConfiguration()
    {
        $inputData = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'dependencies' => ['feature_one', 'feature_two'],
                'routes' => ['oro_feature_route'],
                'configuration' => ['oro_feature', 'oro_another'],
                'entities' => [],
                'field_configs' => [],
                'strategy' => 'affirmative',
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' => true,
                'sidebar_widgets' => ['sidebar_widget1', 'sidebar_widget2'],
                'dashboard_widgets' => ['dashboard_widget1', 'dashboard_widget2'],
                'cron_jobs' => ['cron_job1', 'cron_job2'],
                'api_resources' => ['api_resource1', 'api_resource2'],
                'navigation_items' => ['navigation_item1', 'navigation_item2'],
                'operations' => ['operation1', 'operation2'],
                'workflows' => ['workflow1', 'workflow2'],
                'processes' => ['processe1', 'processe2'],
                'placeholder_items' => ['placeholder_item1', 'placeholder_item2'],
                'mq_topics' => ['mq.topic1', 'mq.topic2']
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
                'field_configs' => [],
                'strategy' => 'affirmative',
                'allow_if_all_abstain' => true,
                'allow_if_equal_granted_denied' => true,
                'commands' => [],
                'sidebar_widgets' => ['sidebar_widget1', 'sidebar_widget2'],
                'dashboard_widgets' => ['dashboard_widget1', 'dashboard_widget2'],
                'cron_jobs' => ['cron_job1', 'cron_job2'],
                'api_resources' => ['api_resource1', 'api_resource2'],
                'navigation_items' => ['navigation_item1', 'navigation_item2'],
                'operations' => ['operation1', 'operation2'],
                'workflows' => ['workflow1', 'workflow2'],
                'processes' => ['processe1', 'processe2'],
                'placeholder_items' => ['placeholder_item1', 'placeholder_item2'],
                'mq_topics' => ['mq.topic1', 'mq.topic2']
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($inputData));
    }

    /**
     * @dataProvider processInvalidConfigurationProvider
     *
     * @param array $inputData
     * @param string $expectedExceptionMessage
     */
    public function testProcessInvalidConfiguration(array $inputData, $expectedExceptionMessage)
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->processConfiguration($inputData);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processInvalidConfigurationProvider()
    {
        return [
            'incorrect root' => [
                'input' => [
                    'feature1' => 'not array value'
                ],
                'message' => 'Invalid type for path "features.feature1". Expected array, but got string'
            ],
            'incorrect label' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                    ]
                ],
                'message' => 'The child node "label" at path "features.feature1" must be configured'
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
                    'Expected scalar, but got array'
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
                    'Expected array, but got string'
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
                    'Expected array, but got string'
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
                    'Expected array, but got integer'
            ],
            'incorrect strategy' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'strategy' => 'not supported'
                    ]
                ],
                'message' => 'The "strategy" can be "'
                    . FeatureChecker::STRATEGY_AFFIRMATIVE
                    . '", "' . FeatureChecker::STRATEGY_CONSENSUS. '" or "'
                    . FeatureChecker::STRATEGY_UNANIMOUS. '.'
            ],
            'incorrect allow_if_all_abstain' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'allow_if_all_abstain' => 'not_bool'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.allow_if_all_abstain". ' .
                             'Expected boolean, but got string'
            ],
            'incorrect allow_if_equal_granted_denied' => [
                'input' => [
                    'feature1' => [
                        'label' => 'Feature 1 Label',
                        'allow_if_equal_granted_denied' => 'not_bool'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.allow_if_equal_granted_denied". ' .
                             'Expected boolean, but got string'
            ],
        ];
    }

    /**
     * @param array $inputData
     * @return array
     */
    protected function processConfiguration(array $inputData)
    {
        $processor = new Processor();

        return $processor->processConfiguration($this->configuration, [$inputData]);
    }
}
