<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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
     * @var DefinitionQueryConstraint
     */
    protected $constraint;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityConfigProvider;

    /**
     * @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldProvider;

    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new DefinitionQueryConstraint();

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);

        $this->validator = new DefinitionQueryValidator($this->entityConfigProvider, $this->fieldProvider);
        $this->validator->initialize($this->context);
    }

    public function testValidateWithNonAbstractQueryDesignerObject()
    {
        $this->context->expects($this->never())
            ->method('buildViolation');
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValidateEmptyQueryWithNonSupportedRootClass()
    {
        $query = new QueryDesignerModel();
        $entity = 'Acme\NonSupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition('');

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(false);

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
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition('[]');

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateNullQueryWitSupportedRootClass()
    {
        $query = new QueryDesignerModel();
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedSimpleColumn()
    {
        $query = new QueryDesignerModel();
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
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

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->fieldProvider->expects($this->exactly(3))
            ->method('getFields')
            ->with($entity, true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ],
                [
                    'name' => 'name',
                    'type' => 'string',
                    'label' => 'Name'
                ]
            ]);

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
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ]
        ));

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->fieldProvider->expects($this->at(0))
            ->method('getFields')
            ->with($entity, true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ],
                [
                    'name' => 'parent',
                    'type' => 'integer',
                    'label' => 'Parent'
                ]
            ]);

        $this->fieldProvider->expects($this->at(1))
            ->method('getFields')
            ->with('Acme\ParentEntity', true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ]
            ]);

        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($query, $this->constraint);
    }

    public function testValidateWitNonSupportedJoinIdentifierColumn()
    {
        $query = new QueryDesignerModel();
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentNonSupportedEntity::id|left']
                ]
            ]
        ));

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->fieldProvider->expects($this->at(0))
            ->method('getFields')
            ->with($entity, true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ],
                [
                    'name' => 'parent',
                    'type' => 'integer',
                    'label' => 'Parent'
                ]
            ]);

        $this->fieldProvider->expects($this->at(1))
            ->method('getFields')
            ->with('Acme\ParentNonSupportedEntity', true, true)
            ->willReturn([]);

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
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ]
        ));

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->fieldProvider->expects($this->at(0))
            ->method('getFields')
            ->with($entity, true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ]
            ]);

        $this->fieldProvider->expects($this->at(1))
            ->method('getFields')
            ->with('Acme\ParentEntity', true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ]
            ]);

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
        $entity = 'Acme\SupportedEntity';
        $query->setEntity($entity);
        $query->setDefinition(json_encode(
            [
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::non_supported|left']
                ]
            ]
        ));

        $this->entityConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $this->fieldProvider->expects($this->at(0))
            ->method('getFields')
            ->with($entity, true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ],
                [
                    'name' => 'parent',
                    'type' => 'integer',
                    'label' => 'Parent'
                ]
            ]);

        $this->fieldProvider->expects($this->at(1))
            ->method('getFields')
            ->with('Acme\ParentEntity', true, true)
            ->willReturn([
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'label' => 'Id'
                ]
            ]);

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
