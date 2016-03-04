<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\LoadVirtualFields;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;

class LoadVirtualFieldsTest extends ConfigProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $virtualFieldProvider;

    /** @var LoadVirtualFields */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->virtualFieldProvider = $this
            ->getMock('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface');

        $this->processor = new LoadVirtualFields($this->virtualFieldProvider);
    }

    /**
     * @dataProvider virtualFieldDataProvider
     */
    public function testProcess($virtualFieldQuery, $expectedFieldConfig = null, $withDescription = false)
    {
        $fieldName = 'testField';

        /** @var EntityDefinitionConfig $definition */
        $definition = $this->createConfigObject([]);

        $this->virtualFieldProvider->expects($this->once())
            ->method('getVirtualFields')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn([$fieldName]);
        $this->virtualFieldProvider->expects($this->once())
            ->method('getVirtualFieldQuery')
            ->with(self::TEST_CLASS_NAME, $fieldName)
            ->willReturn($virtualFieldQuery);

        if ($withDescription) {
            $this->context->setExtras([new DescriptionsConfigExtra()]);
        }
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        if (null === $expectedFieldConfig) {
            $this->assertFalse($definition->hasField($fieldName));
        } else {
            $this->assertEquals(
                $expectedFieldConfig,
                $definition->getField($fieldName)->toArray()
            );
        }
    }

    public function virtualFieldDataProvider()
    {
        return [
            'supported'                                                    => [
                'virtualFieldQuery'   => [
                    'select' => [
                        'expr'  => 'target.name',
                        'label' => 'testField.label',
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'entity.testRel', 'alias' => 'target']
                        ]
                    ]
                ],
                'expectedFieldConfig' => [
                    'property_path' => 'testRel.name'
                ]
            ],
            'supported with description'                                   => [
                'virtualFieldQuery'   => [
                    'select' => [
                        'expr'  => 'target.name',
                        'label' => 'testField.label',
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'entity.testRel', 'alias' => 'target']
                        ]
                    ]
                ],
                'expectedFieldConfig' => [
                    'property_path' => 'testRel.name',
                    'label'         => new Label('testField.label')
                ],
                'withDescription'     => true
            ],
            'not supported, due select.expr'                               => [
                'virtualFieldQuery' => [
                    'select' => [
                        'expr'  => 'COALESCE(target.name, target.label)',
                        'label' => 'testField.label',
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'entity.testRel', 'alias' => 'target']
                        ]
                    ]
                ],
            ],
            'not supported, due no joins'                                  => [
                'virtualFieldQuery' => [
                    'select' => [
                        'expr'  => 'target.name',
                        'label' => 'testField.label',
                    ],
                ],
            ],
            'supported, an alias from select.expr does not exist in joins' => [
                'virtualFieldQuery'   => [
                    'select' => [
                        'expr'  => 'entity.name',
                        'label' => 'testField.label',
                    ],
                    'join'   => [
                        'left' => [
                            ['join' => 'entity.testRel', 'alias' => 'target1']
                        ]
                    ]
                ],
                'expectedFieldConfig' => [
                    'property_path' => 'name'
                ]
            ],
            'not supported, due to join condition'                         => [
                'virtualFieldQuery'   => [
                    'select' => [
                        'expr'  => 'target.name',
                        'label' => 'testField.label',
                    ],
                    'join'   => [
                        'left' => [
                            [
                                'join'          => 'entity.testRel',
                                'alias'         => 'target',
                                'conditionType' => 'WITH',
                                'condition'     => 'target.primary = true'
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }
}
