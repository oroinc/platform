<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganizationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NameOrOrganizationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new NameOrOrganizationValidator();
    }

    protected function createContext()
    {
        $this->constraint = new NameOrOrganization();
        $this->propertyPath = '';

        return parent::createContext();
    }

    public function testConfiguration(): void
    {
        self::assertEquals(NameOrOrganizationValidator::class, $this->constraint->validatedBy());

        self::assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testNotQuoteProduct(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param mixed $data
     */
    public function testValidData($data): void
    {
        $this->validator->validate($data, $this->constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $data
     */
    public function testInvalidData($data): void
    {
        $this->validator->validate($data, $this->constraint);
        $this
            ->buildViolation($this->constraint->firstNameMessage)
            ->atPath('firstName')
            ->buildNextViolation($this->constraint->lastNameMessage)
            ->atPath('lastName')
            ->buildNextViolation($this->constraint->organizationMessage)
            ->atPath('organization')
            ->assertRaised();
    }

    public function validDataProvider(): array
    {
        return [
            'empty first name' => [
                'data' => (new Address())->setLastName('test last name')->setOrganization('test organization')
            ],
            'empty last name' => [
                'data' => (new Address())->setFirstName('test first name')->setOrganization('test organization')
            ],
            'empty organization' => [
                'data' => (new Address())->setFirstName('test first name')->setLastName('test last name')
            ],
            'empty first name and last name' => [
                'data' => (new Address())->setOrganization('test organization')
            ],
            'filled' => [
                'data' => (new Address())
                    ->setFirstName('test first name')
                    ->setLastName('test last name')
                    ->setOrganization('test organization')
            ]
        ];
    }

    public function invalidDataProvider(): array
    {
        return [
            'empty' => [
                'data' => new Address()
            ],
            'empty first name and organization' => [
                'data' => (new Address())->setLastName('test last name')
            ],
            'empty last name and organization' => [
                'data' => (new Address())->setFirstName('test first name')
            ]
        ];
    }
}
