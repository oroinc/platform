<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\DefinitionQueryConstraint;
use Oro\Bundle\QueryDesignerBundle\Validator\DefinitionQueryValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class DefinitionQueryValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DefinitionQueryValidator
     */
    protected $validator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    /**
     * @var DefinitionQueryConstraint
     */
    protected $constraint;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    protected function setUp()
    {
        $this->fieldsProvider = $this->createMock(EntityWithFieldsProvider::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new DefinitionQueryConstraint();
        $this->validator = new DefinitionQueryValidator($this->fieldsProvider);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithNonAbstractQueryDesignerObject()
    {
        $this->fieldsProvider->expects($this->never())
            ->method('getFields');
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateEmptyQueryWithNonSupportedRootClass()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\NonSupportedEntity');
        $query->setDefinition('');

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $violation = $this->createMock(ConstraintViolationBuilder::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.query_designer.not_accessible_class')
            ->willReturn($violation);
        $violation->expects($this->once())
            ->method('setParameter')
            ->with('%className%', 'Acme\NonSupportedEntity')
            ->willReturn($violation);

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateEmptyQueryWitSupportedRootClass()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition('[]');

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateNullQueryWitSupportedRootClass()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedSimpleColumn()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition(json_encode(
            [
                'columns'          => [
                    ['name' => 'id'],
                    ['name' => 'name'],
                    ['name' => 'non_supported']
                ],
                'filters'          => [
                    ['columnName' => 'id'],
                    [],
                    ['name' => 'non_supported']
                ],
                'grouping_columns' => [
                    ['name' => 'id']
                ]
            ]
        ));

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id'],
                            ['name' => 'name']
                        ]
                    ]
                ]
            );

        $violation = $this->createMock(ConstraintViolationBuilder::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.query_designer.not_accessible_class_column')
            ->willReturn($violation);
        $violation->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnMap([
                ['%className%', 'Acme\SupportedEntity', $violation],
                ['%columnName%', 'non_supported', $violation]
            ]);

        $this->validator->validate($query, $this->constraint);
    }


    public function testValidateWitSupportedIdentifierColumn()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ]
        ));

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id'],
                            ['name' => 'parent']
                        ]
                    ],
                    'Acme\ParentEntity'    => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedJoinIdentifierColumn()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentNonSupportedEntity::id|left']
                ]
            ]
        ));

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id'],
                            ['name' => 'parent']
                        ]
                    ]
                ]
            );

        $violation = $this->createMock(ConstraintViolationBuilder::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.query_designer.not_accessible_class')
            ->willReturn($violation);
        $violation->expects($this->once())
            ->method('setParameter')
            ->with('%className%', 'Acme\ParentNonSupportedEntity')
            ->willReturn($violation);

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedRootColumnInIdentifierColumn()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ]
        ));

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ],
                    'Acme\ParentEntity'    => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $violation = $this->createMock(ConstraintViolationBuilder::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.query_designer.not_accessible_class_column')
            ->willReturn($violation);
        $violation->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnMap([
                ['%className%', 'Acme\SupportedEntity', $violation],
                ['%columnName%', 'parent', $violation]
            ]);

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedJoinColumnInIdentifierColumn()
    {
        $query = new QueryDesignerModel();
        $query->setEntity('Acme\SupportedEntity');
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::non_supported|left']
                ]
            ]
        ));

        $this->fieldsProvider->expects($this->once())
            ->method('getFields')
            ->willReturn(
                [
                    'Acme\SupportedEntity' => [
                        'fields' => [
                            ['name' => 'id'],
                            ['name' => 'parent']
                        ]
                    ],
                    'Acme\ParentEntity'    => [
                        'fields' => [
                            ['name' => 'id']
                        ]
                    ]
                ]
            );

        $violation = $this->createMock(ConstraintViolationBuilder::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with('oro.query_designer.not_accessible_class_column')
            ->willReturn($violation);
        $violation->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturnMap([
                ['%className%', 'Acme\ParentEntity', $violation],
                ['%columnName%', 'non_supported', $violation]
            ]);

        $this->validator->validate($query, $this->constraint);
    }
}
