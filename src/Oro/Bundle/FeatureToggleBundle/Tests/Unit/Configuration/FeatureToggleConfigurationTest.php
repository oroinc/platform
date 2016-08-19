<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Unit\Configuration;

use Oro\Bundle\FeatureToggleBundle\Configuration\FeatureToggleConfiguration;

class FeatureToggleConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FeatureToggleConfiguration
     */
    protected $configuration;

    public function setUp()
    {
        $this->configuration = new FeatureToggleConfiguration();
    }

    public function testProcessEmptyConfiguration()
    {
        $this->assertEquals([], $this->configuration->processConfiguration([]));
    }

    public function testProcessMinValidConfiguration()
    {
        $inputData = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label'
            ],
        ];

        $expected = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'strategy' => 'unanimous',
                'dependency' => [],
                'route' => [],
                'workflow' => [],
                'operation' => [],
                'process' => [],
                'configuration' => [],
                'api' => []
            ]
        ];

        $this->assertEquals($expected, $this->configuration->processConfiguration($inputData));
    }

    public function testProcessFullValidConfiguration()
    {
        $inputData = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'strategy' => 'some_strategy',
                'dependency' => ['feature_one', 'feature_two'],
                'route' => ['oro_feature_route'],
                'workflow' => ['feature_workflow_one', 'feature_workflow_two'],
                'operation' => ['feature_operation'],
                'process' => ['feature_process'],
                'configuration' => ['oro_feature', 'oro_another'],
                'api' => ['Oro\FeatureBundle\Entity\SomeEntity']
            ],
        ];

        $expected = [
            'feature1' => [
                'toggle' => 'oro_feature.test.feature_enabled',
                'label' => 'Feature 1 Label',
                'strategy' => 'some_strategy',
                'dependency' => ['feature_one', 'feature_two'],
                'route' => ['oro_feature_route'],
                'workflow' => ['feature_workflow_one', 'feature_workflow_two'],
                'operation' => ['feature_operation'],
                'process' => ['feature_process'],
                'configuration' => ['oro_feature', 'oro_another'],
                'api' => ['Oro\FeatureBundle\Entity\SomeEntity']
            ]
        ];

        $this->assertEquals($expected, $this->configuration->processConfiguration($inputData));
    }

    /**
     * @dataProvider processInvalidConfigurationProvider
     *
     * @param array $inputData
     * @param string $expectedExceptionMessage
     */
    public function testProcessInvalidConfiguration(array $inputData, $expectedExceptionMessage)
    {
        $this->setExpectedException(
            'Symfony\Component\Config\Definition\Exception\InvalidConfigurationException',
            $expectedExceptionMessage
        );

        $this->configuration->processConfiguration($inputData);
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
            'incorrect toggle' => [
                'input' => [
                    'feature1' => []
                ],
                'message' => 'The child node "toggle" at path "features.feature1" must be configured'
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
            'incorrect strategy' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'strategy' => ['array']
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.strategy". ' .
                    'Expected scalar, but got array'
            ],
            'incorrect dependency' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'dependency' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.dependency". ' .
                    'Expected array, but got string'
            ],
            'incorrect route' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'route' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.route". ' .
                    'Expected array, but got string'
            ],
            'incorrect workflow' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'workflow' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.workflow". ' .
                    'Expected array, but got string'
            ],
            'incorrect operation' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'operation' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.operation". ' .
                    'Expected array, but got string'
            ],
            'incorrect process' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'process' => ''
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.process". ' .
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
            'incorrect api' => [
                'input' => [
                    'feature1' => [
                        'toggle' => 'oro_feature.test.feature_enabled',
                        'label' => 'Feature 1 Label',
                        'api' => 'not_array'
                    ]
                ],
                'message' => 'Invalid type for path "features.feature1.api". ' .
                    'Expected array, but got string'
            ],
        ];
    }
}
