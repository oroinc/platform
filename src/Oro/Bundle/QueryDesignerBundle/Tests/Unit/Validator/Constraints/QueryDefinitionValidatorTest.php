<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryDefinition;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\QueryDefinitionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryDefinitionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var EntityFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldProvider;

    protected function createValidator(): QueryDefinitionValidator
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->fieldProvider = $this->createMock(EntityFieldProvider::class);

        return new QueryDefinitionValidator($this->entityConfigProvider, $this->fieldProvider);
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new QueryDefinition());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new QueryDefinition());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner($rootEntityClass, 'invalid json');

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $this->validator->validate($value, new QueryDefinition());
        $this->assertNoViolation();
    }

    public function testEmptyQueryWithNonSupportedRootClass(): void
    {
        $rootEntityClass = 'Acme\NonSupportedEntity';
        $value = new QueryDesigner($rootEntityClass);

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(false);

        $constraint = new QueryDefinition();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameters(['%className%' => 'Acme\NonSupportedEntity'])
            ->assertRaised();
    }

    public function testNullQueryWithSupportedRootClass(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner($rootEntityClass);

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $this->validator->validate($value, new QueryDefinition());
        $this->assertNoViolation();
    }

    public function testNonSupportedSimpleColumn(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner(
            $rootEntityClass,
            QueryDefinitionUtil::encodeDefinition([
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
            ])
        );

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $this->fieldProvider->expects(self::exactly(3))
            ->method('getEntityFields')
            ->with(
                $rootEntityClass,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
            )
            ->willReturn([
                ['name' => 'id', 'type' => 'integer', 'label' => 'Id'],
                ['name' => 'name', 'type' => 'string', 'label' => 'Name']
            ]);

        $constraint = new QueryDefinition();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->messageColumn)
            ->setParameters(['%className%' => 'Acme\SupportedEntity', '%columnName%' => 'non_supported'])
            ->assertRaised();
    }

    public function testSupportedIdentifierColumn(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner(
            $rootEntityClass,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ])
        );

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $options = EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS;
        $this->fieldProvider->expects(self::exactly(2))
            ->method('getEntityFields')
            ->withConsecutive(
                [$rootEntityClass, $options],
                ['Acme\ParentEntity', $options]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id'],
                    ['name' => 'parent', 'type' => 'integer', 'label' => 'Parent']
                ],
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id']
                ]
            );

        $this->validator->validate($value, new QueryDefinition());
        $this->assertNoViolation();
    }

    public function testNonSupportedJoinIdentifierColumn(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner(
            $rootEntityClass,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'parent+Acme\ParentNonSupportedEntity::id|left']
                ]
            ])
        );

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $options = EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS;
        $this->fieldProvider->expects(self::exactly(2))
            ->method('getEntityFields')
            ->withConsecutive(
                [$rootEntityClass, $options],
                ['Acme\ParentNonSupportedEntity', $options]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id'],
                    ['name' => 'parent', 'type' => 'integer', 'label' => 'Parent']
                ],
                []
            );

        $constraint = new QueryDefinition();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameters(['%className%' => 'Acme\ParentNonSupportedEntity'])
            ->assertRaised();
    }

    public function testNonSupportedRootColumnInIdentifierColumn(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner(
            $rootEntityClass,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::id|left']
                ]
            ])
        );

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $options = EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS;
        $this->fieldProvider->expects(self::exactly(2))
            ->method('getEntityFields')
            ->withConsecutive(
                [$rootEntityClass, $options],
                ['Acme\ParentEntity', $options]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id']
                ],
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id']
                ]
            );

        $constraint = new QueryDefinition();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->messageColumn)
            ->setParameters(['%className%' => 'Acme\SupportedEntity', '%columnName%' => 'parent'])
            ->assertRaised();
    }

    public function testNonSupportedJoinColumnInIdentifierColumn(): void
    {
        $rootEntityClass = 'Acme\SupportedEntity';
        $value = new QueryDesigner(
            $rootEntityClass,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'parent+Acme\ParentEntity::non_supported|left']
                ]
            ])
        );

        $this->entityConfigProvider->expects(self::once())
            ->method('hasConfig')
            ->with($rootEntityClass)
            ->willReturn(true);

        $options = EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS;
        $this->fieldProvider->expects(self::exactly(2))
            ->method('getEntityFields')
            ->withConsecutive(
                [$rootEntityClass, $options],
                ['Acme\ParentEntity', $options]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id'],
                    ['name' => 'parent', 'type' => 'integer', 'label' => 'Parent']
                ],
                [
                    ['name' => 'id', 'type' => 'integer', 'label' => 'Id']
                ]
            );

        $constraint = new QueryDefinition();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->messageColumn)
            ->setParameters(['%className%' => 'Acme\ParentEntity', '%columnName%' => 'non_supported'])
            ->assertRaised();
    }
}
