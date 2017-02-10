<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeActionConfigHelper;

class MergeActionConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var MergeActionConfigHelper */
    protected $mergeActionConfigHelper;

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
            'key2' => 'value 2',
        ];
        $actionConfig = [
            'key1' => 'action value 1',
            'key3' => 'action value 3',
        ];

        self::assertEquals(
            [
                'key1' => 'action value 1',
                'key2' => 'value 2',
                'key3' => 'action value 3',
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
                'action4' => null,
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
                'action5' => null,
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
                    'action5' => null,
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
                ],
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
}
