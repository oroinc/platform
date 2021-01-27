<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NotEmptyColumns;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NotEmptyColumnsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyColumnsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotEmptyColumnsValidator
    {
        return new NotEmptyColumnsValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NotEmptyColumns());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new NotEmptyColumns());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $value = new QueryDesigner('Test\Entity', 'invalid json');
        $this->validator->validate($value, new NotEmptyColumns());
        $this->assertNoViolation();
    }

    public function testEmptyDefinition(): void
    {
        $value = new QueryDesigner();
        $constraint = new NotEmptyColumns();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testNoFilters(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'filters' => [
                    ['columnName' => 'testColumn', 'criterion' => ['filter' => 'string']]
                ]
            ])
        );

        $constraint = new NotEmptyColumns();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testEmptyColumns(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [],
                'filters' => [
                    ['columnName' => 'testColumn', 'criterion' => ['filter' => 'string']]
                ]
            ])
        );

        $constraint = new NotEmptyColumns();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testHasColumns(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => '', 'sorting' => '']
                ],
                'filters' => [
                    ['columnName' => 'testColumn', 'criterion' => ['filter' => 'string']]
                ]
            ])
        );
        $this->validator->validate($value, new NotEmptyColumns());
        $this->assertNoViolation();
    }
}
