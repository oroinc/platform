<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\MergeConfig\MergeActionConfigHelper;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MergeActionConfigHelperTest extends \PHPUnit\Framework\TestCase
{
    private MergeActionConfigHelper $mergeActionConfigHelper;

    #[\Override]
    protected function setUp(): void
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
                'field1' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'field1' => null
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

    public function testMergeActionDisabledMetaPropertiesConfig()
    {
        $config = [
            'disabled_meta_properties' => ['prop1', 'prop3']
        ];
        $actionConfig = [
            'disabled_meta_properties' => ['prop1', 'prop2']
        ];

        self::assertEquals(
            [
                'disabled_meta_properties' => ['prop1', 'prop3', 'prop2']
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionDisabledMetaPropertiesConfigWhenActionDoesNotHaveDisabledMetaProperties()
    {
        $config = [
            'disabled_meta_properties' => ['prop1', 'prop2']
        ];
        $actionConfig = [];

        self::assertEquals(
            $config,
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFieldsConfig()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'description' => 'description 1'
                ],
                'field2' => [
                    'description' => 'description 2'
                ],
                'field3' => null,
                'field4' => null
            ]
        ];
        $actionConfig = [
            'fields' => [
                'field1' => [
                    'description' => 'action description 1'
                ],
                'field2' => null,
                'field3' => [
                    'description' => 'action description 3'
                ],
                'field5' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'field1' => [
                        'description' => 'action description 1'
                    ],
                    'field2' => [
                        'description' => 'description 2'
                    ],
                    'field3' => [
                        'description' => 'action description 3'
                    ],
                    'field4' => null,
                    'field5' => null
                ]
            ],
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionFieldsConfigWhenActionDoesNotHaveFields()
    {
        $config = [
            'fields' => [
                'field1' => [
                    'description' => 'description 1'
                ],
                'field2' => [
                    'description' => 'description 2'
                ],
                'field3' => null,
                'field4' => null
            ]
        ];
        $actionConfig = [];

        self::assertEquals(
            $config,
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, true)
        );
    }

    public function testMergeActionConfigWhenNoActionsInConfigWithoutStatusCodes()
    {
        $config = [];
        $actionConfig = [
            'exclude'      => true,
            'fields'       => [
                'field1' => null
            ],
            'status_codes' => [
                'code1' => null
            ]
        ];

        self::assertEquals(
            [
                'fields' => [
                    'field1' => null
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
                'field1' => null
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
                    'field1' => null
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
                'field1' => null
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
                    'field1' => null
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

    /**
     * @dataProvider mergeActionUpsertConfigDataProvider
     */
    public function testMergeActionUpsertConfig(array $config, array $actionConfig, array $expectedConfig)
    {
        self::assertEquals(
            $expectedConfig,
            $this->mergeActionConfigHelper->mergeActionConfig($config, $actionConfig, false)
        );
    }

    public static function mergeActionUpsertConfigDataProvider(): array
    {
        return [
            'action only: enabled'              => [
                'config'         => [],
                'actionConfig'   => [
                    'upsert' => ['disable' => false]
                ],
                'expectedConfig' => [
                    'upsert' => ['disable' => false]
                ]
            ],
            'action only: disabled'             => [
                'config'         => [],
                'actionConfig'   => [
                    'upsert' => ['disable' => true]
                ],
                'expectedConfig' => [
                    'upsert' => ['disable' => true]
                ]
            ],
            'action only: add, remove'          => [
                'config'         => [],
                'actionConfig'   => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2']]
                ],
                'expectedConfig' => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2']]
                ]
            ],
            'action only: add, remove, replace' => [
                'config'         => [],
                'actionConfig'   => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2'], 'replace' => ['field3']]
                ],
                'expectedConfig' => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2'], 'replace' => ['field3']]
                ]
            ],
            'enabled'                           => [
                'config'         => [
                    'upsert' => ['disable' => true, 'add' => ['field1']]
                ],
                'actionConfig'   => [
                    'upsert' => ['disable' => false]
                ],
                'expectedConfig' => [
                    'upsert' => ['disable' => false, 'add' => ['field1']]
                ]
            ],
            'disabled'                          => [
                'config'         => [
                    'upsert' => ['disable' => false, 'add' => ['field1']]
                ],
                'actionConfig'   => [
                    'upsert' => ['disable' => true]
                ],
                'expectedConfig' => [
                    'upsert' => ['disable' => true]
                ]
            ],
            'add, remove'                       => [
                'config'         => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2'], 'replace' => ['field3']]
                ],
                'actionConfig'   => [
                    'upsert' => ['add' => ['field11'], 'remove' => ['field21']]
                ],
                'expectedConfig' => [
                    'upsert' => [
                        'add'     => ['field1', 'field11'],
                        'remove'  => ['field2', 'field21'],
                        'replace' => ['field3']
                    ]
                ]
            ],
            'add, remove, replace'              => [
                'config'         => [
                    'upsert' => ['add' => ['field1'], 'remove' => ['field2'], 'replace' => ['field3']]
                ],
                'actionConfig'   => [
                    'upsert' => ['add' => ['field11'], 'remove' => ['field21'], 'replace' => ['field31']]
                ],
                'expectedConfig' => [
                    'upsert' => ['replace' => ['field31']]
                ]
            ]
        ];
    }
}
