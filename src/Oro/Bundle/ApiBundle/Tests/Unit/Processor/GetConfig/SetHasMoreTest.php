<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\SetHasMore;

class SetHasMoreTest extends ConfigProcessorTestCase
{
    /** @var SetHasMore */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetHasMore();
    }

    public function testProcessForNotCompletedConfig()
    {
        $config = [
            'fields' => [
                'field1' => null
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'fields' => [
                    'field1' => null
                ]
            ],
            $configObject
        );
    }

    public function testProcessForCompletedConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                'field1' => null,
                'field2' => [
                    'exclusion_policy' => 'all',
                    'target_class'     => 'Test\Field2Target',
                    'fields'           => [
                        'field21' => null,
                        'field22' => [
                            'exclusion_policy' => 'all',
                            'target_class'     => 'Test\Field22Target',
                            'fields'           => [
                                'field221' => null
                            ]
                        ]
                    ]
                ]
            ]
        ];

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertConfig(
            [
                'exclusion_policy' => 'all',
                'has_more'         => true,
                'fields'           => [
                    'field1' => null,
                    'field2' => [
                        'exclusion_policy' => 'all',
                        'target_class'     => 'Test\Field2Target',
                        'has_more'         => true,
                        'fields'           => [
                            'field21' => null,
                            'field22' => [
                                'exclusion_policy' => 'all',
                                'target_class'     => 'Test\Field22Target',
                                'has_more'         => true,
                                'fields'           => [
                                    'field221' => null
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $configObject
        );
    }
}
