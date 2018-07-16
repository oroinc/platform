<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeActionConfigHelper;

class MergeActionConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var MergeActionConfigHelper */
    private $mergeActionConfigHelper;

    protected function setUp()
    {
        $this->mergeActionConfigHelper = new MergeActionConfigHelper();
    }

    public function testMergeEmptyActionConfig()
    {
        $config = [];
        $actionConfig = [];

        self::assertEquals(
            [],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionConfigWhenNoActionsInConfig()
    {
        $config = [];
        $actionConfig = [
            'exclude' => true,
            'fields'  => [
                'action1' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'action1' => null
                ]
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionConfig()
    {
        $config = [
            'key1' => 'value 1',
            'key2' => 'value 2'
        ];
        $actionConfig = [
            'key1' => 'action value 1',
            'key3' => 'action value 3'
        ];

        self::assertEquals(
            [
                'key1' => 'action value 1',
                'key2' => 'value 2',
                'key3' => 'action value 3'
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFieldsConfig()
    {
        $config = [
            'fields' => [
                'action1' => [
                    'description' => 'description 1'
                ],
                'action2' => [
                    'description' => 'description 2'
                ],
                'action3' => null,
                'action4' => null
            ]
        ];
        $actionConfig = [
            'fields' => [
                'action1' => [
                    'description' => 'action description 1'
                ],
                'action2' => null,
                'action3' => [
                    'description' => 'action description 3'
                ],
                'action5' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'action1' => [
                        'description' => 'action description 1'
                    ],
                    'action2' => [
                        'description' => 'description 2'
                    ],
                    'action3' => [
                        'description' => 'action description 3'
                    ],
                    'action4' => null,
                    'action5' => null
                ]
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionConfigWhenNoActionsInConfigWithoutStatusCodes()
    {
        $config = [];
        $actionConfig = [
            'exclude'      => true,
            'fields'       => [
                'action1' => null
            ],
            'status_codes' => [
                'code1' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'action1' => null
                ]
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, false)
        );
    }

    public function testMergeActionConfigWhenNoActionsInConfigWithStatusCodes()
    {
        $config = [];
        $actionConfig = [
            'exclude'      => true,
            'fields'       => [
                'action1' => null
            ],
            'status_codes' => [
                'code1' => null
            ]
        ];

        $expectedStatusCodes = new StatusCodesConfig();
        $expectedStatusCodes->addCode('code1');

        self::assertEquals(
            [
                'fields'       => [
                    'action1' => null
                ],
                'status_codes' => $expectedStatusCodes
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionStatusCodes()
    {
        $existingStatusCodes = new StatusCodesConfig();
        $config = [
            'status_codes' => $existingStatusCodes
        ];
        $existingStatusCodes->addCode('code1')->setDescription('code 1');
        $existingStatusCodes->addCode('code2')->setDescription('code 2');
        $actionConfig = [
            'exclude'      => true,
            'fields'       => [
                'action1' => null
            ],
            'status_codes' => [
                'code1' => [
                    'description' => 'action code 1'
                ],
                'code3' => [
                    'description' => 'action code 3'
                ]
            ]
        ];

        $expectedStatusCodes = new StatusCodesConfig();
        $expectedStatusCodes->addCode('code1')->setDescription('action code 1');
        $expectedStatusCodes->addCode('code2')->setDescription('code 2');
        $expectedStatusCodes->addCode('code3')->setDescription('action code 3');

        self::assertEquals(
            [
                'fields'       => [
                    'action1' => null
                ],
                'status_codes' => $expectedStatusCodes
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormOptions()
    {
        $config = [
            'form_options' => [
                'option1' => 'value1',
                'option2' => 'value2'
            ]
        ];
        $actionConfig = [
            'form_options' => [
                'option2' => 'another_value2',
                'option3' => 'value3'
            ]
        ];

        self::assertEquals(
            [
                'form_options' => [
                    'option1' => 'value1',
                    'option2' => 'another_value2',
                    'option3' => 'value3'
                ]
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormOptionsWhenActionDoesNotHaveFormOptions()
    {
        $config = [
            'form_options' => ['entity_option' => 'entity_value']
        ];
        $actionConfig = [];

        self::assertEquals(
            [
                'form_options' => ['entity_option' => 'entity_value']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormOptionsWhenEntityDoesNotHaveFormOptions()
    {
        $config = [];
        $actionConfig = [
            'form_options' => ['action_option' => 'action_value']
        ];

        self::assertEquals(
            [
                'form_options' => ['action_option' => 'action_value']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormOptionsWhenFormTypeIsChanged()
    {
        $config = [
            'form_options' => ['entity_option' => 'entity_value']
        ];
        $actionConfig = [
            'form_type'    => 'other_form',
            'form_options' => ['action_option' => 'action_value']
        ];

        self::assertEquals(
            [
                'form_type'    => 'other_form',
                'form_options' => ['action_option' => 'action_value']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormEventSubscribers()
    {
        $config = [
            'form_event_subscriber' => ['entity_subscriber']
        ];
        $actionConfig = [
            'form_event_subscriber' => ['action_subscriber']
        ];

        self::assertEquals(
            [
                'form_event_subscriber' => ['entity_subscriber', 'action_subscriber']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormEventSubscribersWhenActionDoesNotHaveFormEventSubscribers()
    {
        $config = [
            'form_event_subscriber' => ['entity_subscriber']
        ];
        $actionConfig = [];

        self::assertEquals(
            [
                'form_event_subscriber' => ['entity_subscriber']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormEventSubscribersWhenEntityDoesNotHaveFormEventSubscribers()
    {
        $config = [];
        $actionConfig = [
            'form_event_subscriber' => ['action_subscriber']
        ];

        self::assertEquals(
            [
                'form_event_subscriber' => ['action_subscriber']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFormEventSubscribersWhenFormTypeIsChanged()
    {
        $config = [
            'form_event_subscriber' => ['entity_subscriber']
        ];
        $actionConfig = [
            'form_type'             => 'other_form',
            'form_event_subscriber' => ['action_subscriber']
        ];

        self::assertEquals(
            [
                'form_type'             => 'other_form',
                'form_event_subscriber' => ['action_subscriber']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }
}
