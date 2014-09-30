<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\GroupingValidator;

class GroupingValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GroupingValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var GroupingConstraint
     */
    protected $constraint;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->validator = new GroupingValidator(
            $this->translator
        );

        $this->context = $this->getMock('\Symfony\Component\Validator\ExecutionContextInterface');
        $this->validator->initialize($this->context);

        $this->constraint = new GroupingConstraint();
    }

    /**
     * @param array $definition
     * @param mixed $expected
     *
     * @dataProvider groupingDataProvider
     */
    public function testValidate(array $definition, $expected)
    {
        $value = $this->getMockForAbstractClass('Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner');

        $value
            ->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue(json_encode($definition)));

        if ($expected) {
            $this->translator
                ->expects($this->once())
                ->method('trans')
                ->with(
                    $this->isType('string'),
                    $this->equalTo(['%columns%' => $expected])
                );

            $this->context
                ->expects($this->once())
                ->method('addViolation');
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function groupingDataProvider()
    {
        return [
            'empty'                       => [
                [],
                null
            ],
            'single_column'               => [
                [
                    'columns' => [
                        [
                            'name'    => 'columnName',
                            'label'   => 'columnLabel',
                            'func'    => '',
                            'sorting' => '',
                        ]
                    ],
                ],
                null
            ],
            'single_func_column'          => [
                [
                    'columns' => [
                        [
                            'name'    => 'columnName',
                            'label'   => 'columnLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ]
                    ],
                ],
                null
            ],
            'single_func_column_grouping' => [
                [
                    'columns'          => [
                        [
                            'name'    => 'columnName',
                            'label'   => 'columnLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'toGroup',
                            'label'   => 'toGroupLabel',
                            'func'    => '',
                            'sorting' => '',
                        ]
                    ],
                    'grouping_columns' => [],
                ],
                'toGroupLabel'
            ],
            'single_func_column_mixed'    => [
                [
                    'columns'          => [
                        [
                            'name'    => 'columnName',
                            'label'   => 'columnLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'columnName2',
                            'label'   => 'columnLabel2',
                            'func'    => '',
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'toGroup',
                            'label'   => 'toGroupLabel',
                            'func'    => '',
                            'sorting' => '',
                        ]
                    ],
                    'grouping_columns' => [
                        [
                            'name'    => 'columnName2',
                            'label'   => 'columnLabel2',
                            'func'    => '',
                            'sorting' => '',
                        ]
                    ],
                ],
                'toGroupLabel'
            ],
            'full'                        => [
                [
                    'columns'          => [
                        [
                            'name'    => 'columnName',
                            'label'   => 'columnLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'columnName2',
                            'label'   => 'columnLabel2',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'grouped',
                            'label'   => 'groupedLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'toGroup',
                            'label'   => 'toGroupLabel',
                            'func'    => '',
                            'sorting' => '',
                        ],
                        [
                            'name'    => 'toGroup2',
                            'label'   => 'toGroupLabel2',
                            'func'    => '',
                            'sorting' => '',
                        ]
                    ],
                    'grouping_columns' => [
                        [
                            'name'    => 'grouped',
                            'label'   => 'groupedLabel',
                            'func'    => [
                                'type' => 'type'
                            ],
                            'sorting' => '',
                        ]
                    ],
                ],
                'toGroupLabel, toGroupLabel2'
            ],
        ];
    }
}
