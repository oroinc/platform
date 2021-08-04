<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddress;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddressValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NewAddressValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new NewAddressValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new NewAddress();
        $this->propertyPath = '';

        return parent::createContext();
    }

    public function testWithInvalidConstraint(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new Address(), $this->createMock(Constraint::class));
    }

    public function testWithNotAddressEntity(): void
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithNewAddressEntity(): void
    {
        $this->validator->validate(new Address(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithNotNewAddressEntity(): void
    {
        $address = new Address();
        ReflectionUtil::setId($address, 123);
        $this->validator->validate($address, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('')
            ->assertRaised();
    }
}
