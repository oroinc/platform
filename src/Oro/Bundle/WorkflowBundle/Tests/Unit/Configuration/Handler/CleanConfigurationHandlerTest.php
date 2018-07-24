<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\CleanConfigurationHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CleanConfigurationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var CleanConfigurationHandler */
    protected $handler;

    protected function setUp()
    {
        $this->requestStack = new RequestStack();

        $this->handler = new CleanConfigurationHandler($this->requestStack);
    }

    public function testHandleNotApplicable()
    {
        $config = [
            WorkflowConfiguration::NODE_TRANSITIONS => [
                'test_transition' => [
                    'form_options' => [
                        'init_actions' => ['config'],
                        'form_init' => ['config'],
                        'other_node_name' => 'other_node_config'
                    ],
                    'other_node_name' => 'other_node_config'
                ]
            ],
            WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                'test_transition' => [
                    'preactions' => ['config'],
                    'preconditions' => ['config'],
                    'pre_conditions' => ['config'],
                    'conditions' => ['config'],
                    'actions' => ['config'],
                    'post_actions' => ['config'],
                    'other_node_name' => 'other_node_config'
                ]
            ],
            'other_node_name' => 'other_node_config'
        ];

        $this->assertSame($config, $this->handler->handle($config));
    }

    /**
     * @dataProvider handleDataProvider
     *
     * @param array $configuration
     * @param array $expected
     */
    public function testHandle(array $configuration, array $expected)
    {
        $this->requestStack->push(new Request());

        $this->assertEquals($expected, $this->handler->handle($configuration));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return \Generator
     */
    public function handleDataProvider()
    {
        yield 'empty configuration' => [
            'configuration' => [],
            'expected' => []
        ];

        yield 'empty nodes configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => []
            ],
            'expected' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => []
            ]
        ];

        yield 'configuration without data' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'other_node_name' => 'other_node_config'
                        ],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                'other_node_name' => 'other_node_config'
            ],
            'expected' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'other_node_name' => 'other_node_config'
                        ],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                'other_node_name' => 'other_node_config'
            ]
        ];

        yield 'configuration with data' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'form_init' => ['config'],
                            'other_node_name' => 'other_node_config'
                        ],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => ['config'],
                        'preconditions' => ['config'],
                        'conditions' => ['config'],
                        'actions' => ['config'],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                'other_node_name' => 'other_node_config'
            ],
            'expected' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'other_node_name' => 'other_node_config'
                        ],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'other_node_name' => 'other_node_config'
                    ]
                ],
                'other_node_name' => 'other_node_config'
            ]
        ];
    }
}
