<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Validator\Constraints\RequiredRegion;
use Oro\Bundle\AddressBundle\Validator\Constraints\RequiredRegionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RequiredRegionValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new RequiredRegionValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new RequiredRegion();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testConfiguration()
    {
        $this->assertEquals(RequiredRegionValidator::class, $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testAddressWithoutCountry()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegion(null);
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryAndRegion()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($this->createMock(Country::class));
        $address->setRegion($this->createMock(Region::class));
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatDoesNotHaveRegions()
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('hasRegions')
            ->willReturn(false);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegion(null);
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatHasRegions()
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);
        $country->expects($this->once())
            ->method('getName')
            ->willReturn('Country');

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegion(null);
        $this->validator->validate($address, $this->constraint);
        $this
            ->buildViolation($this->constraint->message)
            ->setParameters(['{{ country }}' => 'Country'])
            ->atPath('region')
            ->assertRaised();
    }
}
