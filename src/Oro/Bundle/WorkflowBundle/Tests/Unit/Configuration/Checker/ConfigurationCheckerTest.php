<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Checker;

use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Component\ConfigExpression\ContextAccessor;

class ConfigurationCheckerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigurationChecker */
    protected $checker;

    protected function setUp()
    {
        $this->checker = new ConfigurationChecker(new ContextAccessor());
    }

    /**
     * @dataProvider isCleanDataProvider
     *
     * @param array $config
     * @param $expected
     */
    public function testIsClean(array $config, $expected)
    {
        $this->assertEquals($expected, $this->checker->isClean($config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return \Generator
     */
    public function isCleanDataProvider()
    {
        yield 'empty configuration' => [
            'config' => [],
            'expected' => true
        ];

        yield 'empty nodes configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => []
            ],
            'expected' => true
        ];

        yield 'empty configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => [],
                        'conditions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => true
        ];

        yield 'init_actions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => ['config'],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => [],
                        'conditions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'pre_conditions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => ['config'],
                        'conditions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'conditions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => [],
                        'conditions' => ['config'],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'post_actions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => [],
                        'conditions' => [],
                        'post_actions' => ['config']
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'configuration without data' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'pre_conditions' => [],
                        'conditions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => true
        ];
    }
}
