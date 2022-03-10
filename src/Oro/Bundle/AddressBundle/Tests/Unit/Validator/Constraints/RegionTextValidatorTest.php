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
    protected function createValidator(): RegionTextValidator
    {
        return new RegionTextValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new RegionText();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testAddressWithoutCountry(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegionText('some region');

        $constraint = new RegionText();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithoutCountryAndAddressDoesNotHaveRegionText(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegionText(null);

        $constraint = new RegionText();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatDoesNotHaveRegions(): void
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('hasRegions')
            ->willReturn(false);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegionText('some region');

        $constraint = new RegionText();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatHasRegions(): void
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->once())
            ->method('hasRegions')
            ->willReturn(true);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegionText('some region');

        $constraint = new RegionText();
        $this->validator->validate($address, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testAddressWithCountryThatHasRegionsAndAddressDoesNotHaveRegionText(): void
    {
        $country = $this->createMock(Country::class);
        $country->expects($this->never())
            ->method('hasRegions')
            ->willReturn(false);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegionText(null);

        $constraint = new RegionText();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }
}
