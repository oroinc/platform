<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganizationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NameOrOrganizationValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NameOrOrganizationValidator
    {
        return new NameOrOrganizationValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new NameOrOrganization();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testNotQuoteProduct(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new NameOrOrganization());
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testValidData(Address $value): void
    {
        $constraint = new NameOrOrganization();
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    public function validDataProvider(): array
    {
        return [
            '(int) 0 as first name and last name' => [
                (new Address())->setFirstName(0)->setLastName(0)
            ],
            '(int) 0 as organization' => [
                (new Address())->setOrganization(0)
            ],
            '(string) 0 as first name and last name' => [
                (new Address())->setFirstName('0')->setLastName('0')
            ],
            '(string) 0 as organization' => [
                (new Address())->setOrganization('0')
            ],
            '(float) 0.0 as first name and last name' => [
                (new Address())->setFirstName(0.0)->setLastName(0.0)
            ],
            '(float) 0.0 as organization' => [
                (new Address())->setOrganization(0.0)
            ],
            'empty first name' => [
                (new Address())->setLastName('test last name')->setOrganization('test organization')
            ],
            'empty last name' => [
                (new Address())->setFirstName('test first name')->setOrganization('test organization')
            ],
            'empty organization' => [
                (new Address())->setFirstName('test first name')->setLastName('test last name')
            ],
            'empty first name and last name' => [
                (new Address())->setOrganization('test organization')
            ],
            'filled' => [
                (new Address())
                    ->setFirstName('test first name')
                    ->setLastName('test last name')
                    ->setOrganization('test organization')
            ]
        ];
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testInvalidData(Address $value): void
    {
        $constraint = new NameOrOrganization();
        $this->validator->validate($value, $constraint);

        $this
            ->buildViolation($constraint->firstNameMessage)
            ->atPath('property.path.firstName')
            ->buildNextViolation($constraint->lastNameMessage)
            ->atPath('property.path.lastName')
            ->buildNextViolation($constraint->organizationMessage)
            ->atPath('property.path.organization')
            ->assertRaised();
    }

    public function invalidDataProvider(): array
    {
        return [
            'empty' => [
                new Address()
            ],
            'empty first name and organization' => [
                (new Address())->setLastName('test last name')
            ],
            'empty last name and organization' => [
                (new Address())->setFirstName('test first name')
            ],
            'false as names' => [
                (new Address())->setFirstName(false)->setLastName(false)
            ],
            'false as organization' => [
                (new Address())->setOrganization(false)
            ],
            'array as names' => [
                (new Address())->setFirstName([])->setLastName([])
            ],
            'array as organization' => [
                (new Address())->setOrganization([])
            ],
            'empty string as names' => [
                (new Address())->setFirstName('')->setLastName('')
            ],
            'empty string as organizations' => [
                (new Address())->setOrganization('')
            ],
        ];
    }
}
