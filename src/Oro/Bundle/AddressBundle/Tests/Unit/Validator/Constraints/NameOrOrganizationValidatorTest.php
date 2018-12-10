<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganizationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NameOrOrganizationValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new NameOrOrganizationValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new NameOrOrganization();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testConfiguration()
    {
        $this->assertEquals(NameOrOrganizationValidator::class, $this->constraint->validatedBy());

        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotQuoteProduct()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param mixed $data
     */
    public function testValidData($data)
    {
        $this->validator->validate($data, $this->constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $data
     */
    public function testInvalidData($data)
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

    /**
     * @return array
     */
    public function validDataProvider()
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

    /**
     * @return array
     */
    public function invalidDataProvider()
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
