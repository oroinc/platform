<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ExtendEntityEnumValues;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ExtendEntityEnumValuesValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ExtendEntityEnumValuesValidatorTest extends ConstraintValidatorTestCase
{
    private EnumOptionsProvider $enumOptionsProvider;

    protected function setUp(): void
    {
        $this->enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        parent::setUp();
    }

    protected function createValidator(): ExtendEntityEnumValuesValidator
    {
        return new ExtendEntityEnumValuesValidator($this->enumOptionsProvider);
    }

    public function testValidateWithNull(): void
    {
        $this->validator->validate(null, new ExtendEntityEnumValues());

        $this->assertNoViolation();
    }

    public function testValidateWithNonEnumOption(): void
    {
        $this->validator->validate('string_value', new ExtendEntityEnumValues());

        $this->assertNoViolation();
    }

    public function testValidateWithValidSingleOption(): void
    {
        $enumOption = new EnumOption('test_enum', 'Option 1', 'option1', 1, false);

        $this->enumOptionsProvider
            ->expects($this->once())
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn(['Option 1' => $enumOption->getId()]);

        $this->validator->validate($enumOption, new ExtendEntityEnumValues());

        $this->assertNoViolation();
    }

    public function testValidateWithInvalidSingleOption(): void
    {
        $enumOption = new EnumOption('test_enum', 'Invalid Option', 'invalid', 1, false);

        $this->enumOptionsProvider
            ->expects($this->once())
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn(['Option 1' => 'test_enum.option1']);

        $constraint = new ExtendEntityEnumValues();
        $this->validator->validate($enumOption, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'invalid')
            ->assertRaised();
    }

    public function testValidateWithValidMultipleOptions(): void
    {
        $option1 = new EnumOption('test_enum', 'Option 1', 'option1', 1, false);
        $option2 = new EnumOption('test_enum', 'Option 2', 'option2', 2, false);

        $this->enumOptionsProvider
            ->expects($this->exactly(2))
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn([
                'Option 1' => $option1->getId(),
                'Option 2' => $option2->getId()
            ]);

        $this->validator->validate([$option1, $option2], new ExtendEntityEnumValues());

        $this->assertNoViolation();
    }

    public function testValidateWithMixedValidAndInvalidOptions(): void
    {
        $validOption = new EnumOption('test_enum', 'Valid Option', 'valid', 1, false);
        $invalidOption = new EnumOption('test_enum', 'Invalid Option', 'invalid', 2, false);

        $this->enumOptionsProvider
            ->expects($this->exactly(2))
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn(['Valid Option' => $validOption->getId()]);

        $constraint = new ExtendEntityEnumValues();
        $this->validator->validate([$validOption, $invalidOption], $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'invalid')
            ->assertRaised();
    }

    public function testValidateWithEmptyEnumOptions(): void
    {
        $enumOption = new EnumOption('test_enum', 'Option', 'option', 1, false);

        $this->enumOptionsProvider
            ->expects($this->once())
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn([]);

        $constraint = new ExtendEntityEnumValues();
        $this->validator->validate($enumOption, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'option')
            ->assertRaised();
    }

    public function testValidateWithArrayContainingNull(): void
    {
        $enumOption = new EnumOption('test_enum', 'Option', 'option', 1, false);

        $this->enumOptionsProvider
            ->expects($this->once())
            ->method('getEnumChoicesByCode')
            ->with('test_enum')
            ->willReturn(['Option' => $enumOption->getId()]);

        $this->validator->validate([null, $enumOption], new ExtendEntityEnumValues());

        $this->assertNoViolation();
    }
}
