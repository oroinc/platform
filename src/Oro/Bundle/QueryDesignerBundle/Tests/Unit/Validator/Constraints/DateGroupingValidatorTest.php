<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\DateGrouping;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\DateGroupingValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DateGroupingValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): DateGroupingValidator
    {
        return new DateGroupingValidator();
    }

    public function testUnsupportedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new QueryDesigner(), $this->createMock(Constraint::class));
    }

    public function testNullValueIsValid(): void
    {
        $this->validator->validate(null, new DateGrouping());
        $this->assertNoViolation();
    }

    public function testUnsupportedValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), new DateGrouping());
    }

    public function testInvalidJsonShouldBeIgnored(): void
    {
        $value = new QueryDesigner('Test\Entity', 'invalid json');
        $this->validator->validate($value, new DateGrouping());
        $this->assertNoViolation();
    }

    public function testNoDateGrouping()
    {
        $value = new QueryDesigner();

        $this->validator->validate($value, new DateGrouping());
        $this->assertNoViolation();
    }

    public function testGroupingIsNotAvailable()
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                DateGroupingType::DATE_GROUPING_NAME => []
            ])
        );

        $constraint = new DateGrouping();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->groupByMandatoryMessage)
            ->assertRaised();
    }

    public function testFieldNameIsNotSet()
    {
        $value = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition([
                DateGroupingType::DATE_GROUPING_NAME => [
                    DateGroupingType::USE_DATE_GROUPING_FILTER => true,
                ],
                'grouping_columns'                   => ['testGroup'],
            ])
        );

        $constraint = new DateGrouping();
        $this->validator->validate($value, $constraint);
        $this->buildViolation($constraint->dateFieldMandatoryMessage)
            ->assertRaised();
    }
}
