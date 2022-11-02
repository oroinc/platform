<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NotEmptyFilters;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\NotEmptyFiltersValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NotEmptyFiltersValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NotEmptyFiltersValidator
    {
        return new NotEmptyFiltersValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new NotEmptyFilters());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new NotEmptyFilters());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $value = new QueryDesigner('Test\Entity', 'invalid json');
        $this->validator->validate($value, new NotEmptyFilters());
        $this->assertNoViolation();
    }

    public function testEmptyDefinition(): void
    {
        $value = new QueryDesigner();
        $constraint = new NotEmptyFilters();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testNoFilters(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => '', 'sorting' => '']
                ]
            ])
        );

        $constraint = new NotEmptyFilters();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testEmptyFilters(): void
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    ['name' => 'columnName', 'label' => 'columnLabel', 'func' => '', 'sorting' => '']
                ],
                'filters' => []
            ])
        );

        $constraint = new NotEmptyFilters();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testHasFilters(): void
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
        $this->validator->validate($value, new NotEmptyFilters());
        $this->assertNoViolation();
    }
}
