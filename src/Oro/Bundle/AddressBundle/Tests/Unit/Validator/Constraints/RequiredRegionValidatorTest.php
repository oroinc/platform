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
    protected function createValidator(): RequiredRegionValidator
    {
        return new RequiredRegionValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new RequiredRegion();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testAddressWithoutCountry(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegion(null);

        $constraint = new RequiredRegion();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryAndRegion(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($this->createMock(Country::class));
        $address->setRegion($this->createMock(Region::class));

        $constraint = new RequiredRegion();
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
        $address->setRegion(null);

        $constraint = new RequiredRegion();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithCountryThatHasRegions(): void
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

        $constraint = new RequiredRegion();
        $this->validator->validate($address, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ country }}' => 'Country'])
            ->atPath('property.path.region')
            ->assertRaised();
    }
}
