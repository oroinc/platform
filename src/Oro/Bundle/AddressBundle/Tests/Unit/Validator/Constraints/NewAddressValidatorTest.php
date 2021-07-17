<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddress;
use Oro\Bundle\AddressBundle\Validator\Constraints\NewAddressValidator;
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
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testWithInvalidConstraint()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new Address(), $this->createMock(Constraint::class));
    }

    public function testWithNotAddressEntity()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithNewAddressEntity()
    {
        $this->validator->validate(new Address(), $this->constraint);
        $this->assertNoViolation();
    }

    public function testWithNotNewAddressEntity()
    {
        $address = new Address();
        $this->setId($address, 123);
        $this->validator->validate($address, $this->constraint);
        $this->buildViolation($this->constraint->message)
            ->atPath('')
            ->assertRaised();
    }

    /**
     * Cannot use EntityTrait because setValue declarations in trait and ConstraintValidatorTestCase are different.
     */
    private function setId($entity, $idValue)
    {
        $reflectionClass = new \ReflectionClass($entity);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $idValue);
    }
}
