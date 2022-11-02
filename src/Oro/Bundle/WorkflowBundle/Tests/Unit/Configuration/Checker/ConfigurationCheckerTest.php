<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Checker;

use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Component\ConfigExpression\ContextAccessor;

class ConfigurationCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationChecker */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new ConfigurationChecker(new ContextAccessor());
    }

    /**
     * @dataProvider isCleanDataProvider
     */
    public function testIsClean(array $config, $expected)
    {
        $this->assertEquals($expected, $this->checker->isClean($config));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isCleanDataProvider(): \Generator
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

        yield 'non-empty nodes configuration' => [
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
                        'conditions' => [],
                        'actions' => [],
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
                        'conditions' => [],
                        'actions' => [],
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
                        'conditions' => [],
                        'actions' => [],
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
                        'conditions' => [],
                        'actions' => [],
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
                        'conditions' => [],
                        'actions' => [],
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
                        'conditions' => ['config'],
                        'actions' => [],
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
                        'conditions' => [],
                        'actions' => ['config'],
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
                        'conditions' => [],
                        'actions' => [],
                    ]
                ]
            ],
            'expected' => true
        ];
    }
}
