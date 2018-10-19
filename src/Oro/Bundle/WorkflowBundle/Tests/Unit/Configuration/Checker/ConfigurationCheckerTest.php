<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Checker;

use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Component\ConfigExpression\ContextAccessor;

class ConfigurationCheckerTest extends \PHPUnit\Framework\TestCase
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'form_init configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                            'form_init' => ['config']
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'preactions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => ['config'],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'preconditions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => ['config'],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => ['config'],
                        'conditions' => [],
                        'actions' => [],
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => ['config'],
                        'actions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => false
        ];

        yield 'actions configuration' => [
            'configuration' => [
                WorkflowConfiguration::NODE_TRANSITIONS => [
                    'test_transition' => [
                        'form_options' => [
                            'init_actions' => [],
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => ['config'],
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
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
                            'form_init' => []
                        ]
                    ]
                ],
                WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                    'test_transition' => [
                        'preactions' => [],
                        'preconditions' => [],
                        'pre_conditions' => [],
                        'conditions' => [],
                        'actions' => [],
                        'post_actions' => []
                    ]
                ]
            ],
            'expected' => true
        ];
    }
}
