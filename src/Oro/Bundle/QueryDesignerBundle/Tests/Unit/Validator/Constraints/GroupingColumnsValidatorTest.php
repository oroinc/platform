<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingColumns;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingColumnsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class GroupingColumnsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): GroupingColumnsValidator
    {
        return new GroupingColumnsValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new GroupingColumns());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new GroupingColumns());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $value = new QueryDesigner('Test\Entity', 'invalid json');
        $this->validator->validate($value, new GroupingColumns());
        $this->assertNoViolation();
    }

    public function testSingleColumn(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => '']
                ]
            ])
        );

        $this->validator->validate($value, new GroupingColumns());
        $this->assertNoViolation();
    }

    public function testSingleAggregateFuncColumnAndNoGroupingColumnsSection(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => ['group_type' => 'aggregates']]
                ]
            ])
        );

        $this->validator->validate($value, new GroupingColumns());
        $this->assertNoViolation();
    }

    public function testSingleAggregateFuncColumn(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns'          => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => ['group_type' => 'aggregates']],
                    ['name' => 'toGroup', 'label' => 'toGroupLabel', 'func' => '']
                ],
                'grouping_columns' => []
            ])
        );

        $constraint = new GroupingColumns();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameters(['%columns%' => 'toGroupLabel'])
            ->assertRaised();
    }

    public function testSingleNotAggregateFuncColumn(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns'          => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => ['group_type' => 'converters']],
                    ['name' => 'toGroup', 'label' => 'toGroupLabel', 'func' => '']
                ],
                'grouping_columns' => []
            ])
        );

        $this->validator->validate($value, new GroupingColumns());
        $this->assertNoViolation();
    }

    public function testAggregateAndNotAggregateFuncColumns(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns'          => [
                    ['name' => 'column1', 'label' => 'columnLabel1', 'func' => ['group_type' => 'aggregates']],
                    ['name' => 'column2', 'label' => 'columnLabel2', 'func' => ['group_type' => 'aggregates']],
                    ['name' => 'grouped', 'label' => 'groupedLabel', 'func' => ['group_type' => 'aggregates']],
                    [
                        'name'  => 'notAggregate',
                        'label' => 'notAggregateLabel',
                        'func'  => ['group_type' => 'converters']
                    ],
                    ['name' => 'toGroup1', 'label' => 'toGroupLabel1', 'func' => ''],
                    ['name' => 'toGroup2', 'label' => 'toGroupLabel2', 'func' => '']
                ],
                'grouping_columns' => [
                    ['name' => 'grouped', 'label' => 'groupedLabel']
                ],
            ])
        );

        $constraint = new GroupingColumns();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameters(['%columns%' => 'notAggregateLabel, toGroupLabel1, toGroupLabel2'])
            ->assertRaised();
    }
}
