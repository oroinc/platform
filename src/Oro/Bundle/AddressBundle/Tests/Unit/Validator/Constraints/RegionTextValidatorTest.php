<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Validator\Constraints\RegionText;
use Oro\Bundle\AddressBundle\Validator\Constraints\RegionTextValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class RegionTextValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new RegionTextValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new RegionText();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testConfiguration()
    {
        $this->assertEquals(RegionTextValidator::class, $this->constraint->validatedBy());
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testAddressWithoutCountry()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegionText('some region');
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithoutCountryAndAddressDoesNotHaveRegionText()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegionText(null);
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
        $address->setRegionText('some region');
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatHasRegions()
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegionText('some region');
        $this->validator->validate($address, $this->constraint);
        $this
            ->buildViolation($this->constraint->message)
            ->atPath(null)
            ->assertRaised();
    }

    public function testAddressWithCountryThatHasRegionsAndAddressDoesNotHaveRegionText()
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->never())
            ->method('hasRegions')
            ->willReturn(false);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegionText(null);
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }
}
