<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddress;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddressValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NewAddressValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): NewAddressValidator
    {
        return new NewAddressValidator();
    }

    public function testWithInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Address(), $this->createMock(Constraint::class));
    }

    public function testWithNotAddressEntity(): void
    {
        $constraint = new NewAddress();
        $this->validator->validate(new \stdClass(), $constraint);
        $this->assertNoViolation();
    }

    public function testWithNewAddressEntity(): void
    {
        $constraint = new NewAddress();
        $this->validator->validate(new Address(), $constraint);
        $this->assertNoViolation();
    }

    public function testWithNotNewAddressEntity(): void
    {
        $address = new Address();
        ReflectionUtil::setId($address, 123);

        $constraint = new NewAddress();
        $this->validator->validate($address, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
