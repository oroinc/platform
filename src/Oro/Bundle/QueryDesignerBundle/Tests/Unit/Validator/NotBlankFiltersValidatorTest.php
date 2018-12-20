<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Validator\NotBlankFilters;
use Oro\Bundle\QueryDesignerBundle\Validator\NotBlankFiltersValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class NotBlankFiltersValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NotBlankFiltersValidator
     */
    protected $validator;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var NotBlankFilters
     */
    protected $constraint;

    protected function setUp()
    {
        $this->validator = new NotBlankFiltersValidator();

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);

        $this->constraint = new NotBlankFilters();
    }

    /**
     * @dataProvider invalidDefinitionDataProvider
     * @param array $definition
     */
    public function testValidateInvalid(array $definition)
    {
        /** @var AbstractQueryDesigner|\PHPUnit\Framework\MockObject\MockObject $value */
        $value = $this->getMockForAbstractClass(AbstractQueryDesigner::class);

        $value->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue(json_encode($definition)));

        $this->context
            ->expects($this->once())
            ->method('addViolation');

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function invalidDefinitionDataProvider()
    {
        return [
            'empty' => [
                []
            ],
            'no filters' => [
                [
                    'columns' => [
                        [
                            'name' => 'columnName',
                            'label' => 'columnLabel',
                            'func' => '',
                            'sorting' => '',
                        ]
                    ],
                ]
            ],
            'empty filters' => [
                [
                    'columns' => [
                        [
                            'name' => 'columnName',
                            'label' => 'columnLabel',
                            'func' => '',
                            'sorting' => '',
                        ]
                    ],
                    'filters' => []
                ]
            ]
        ];
    }

    public function testValidateValid()
    {
        $definition = [
            'columns' => [
                [
                    'name' => 'columnName',
                    'label' => 'columnLabel',
                    'func' => '',
                    'sorting' => '',
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'testColumn',
                    'criterion' => [
                        'filter' => 'string',
                        'data' => ['value' => 'a', 'type' => '1']
                    ]
                ]
            ]
        ];

        /** @var AbstractQueryDesigner|\PHPUnit\Framework\MockObject\MockObject $value */
        $value = $this->getMockForAbstractClass(AbstractQueryDesigner::class);

        $value->expects($this->any())
            ->method('getDefinition')
            ->will($this->returnValue(json_encode($definition)));

        $this->context
            ->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($value, $this->constraint);
    }

    public function testValidateUnexpectedType()
    {
        $value = 'test';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Value must be instance of "%s", "%s" given',
                AbstractQueryDesigner::class,
                'string'
            )
        );
        $this->validator->validate($value, $this->constraint);
    }
}
