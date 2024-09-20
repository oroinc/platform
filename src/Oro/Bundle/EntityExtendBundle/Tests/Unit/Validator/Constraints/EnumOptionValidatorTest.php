<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Model\EnumOption;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\EnumOptionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EnumOptionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): EnumOptionValidator
    {
        return new EnumOptionValidator();
    }

    public function testGetTargets()
    {
        $constraint = new Constraints\EnumOption();
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testNotEnumValueOrArray()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new Constraints\EnumOption());
    }

    /**
     * @dataProvider validateForValidValueDataProvider
     */
    public function testValidateForValidValue(string $label)
    {
        $value = (new EnumOption())->setLabel($label);

        $constraint = new Constraints\EnumOption();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validateForValidValueDataProvider(): array
    {
        return [
            ['label' => 'valLabel'],
            ['label' => '0\''],
            ['label' => '0'],
        ];
    }

    public function testValidateEmptyValue()
    {
        $constraint = new Constraints\EnumOption();
        $this->validator->validate(new EnumOption(), $constraint);
        $this->assertNoViolation();
    }

    public function testValidateForValidArrayValue()
    {
        $value = [
            'id'    => 'valId',
            'label' => 'valLabel'
        ];

        $constraint = new Constraints\EnumOption();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateForInvalidValueDataProvider
     */
    public function testValidateForInvalidValue($label)
    {
        $value = (new EnumOption())->setLabel($label);

        $constraint = new Constraints\EnumOption();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $label)
            ->atPath('property.path[label]')
            ->assertRaised();
    }

    public function validateForInvalidValueDataProvider(): array
    {
        return [
            ['label' => '+'],
            ['label' => ' '],
        ];
    }
}
