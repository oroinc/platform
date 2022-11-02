<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Validator\Constraints\ValidRegion;
use Oro\Bundle\AddressBundle\Validator\Constraints\ValidRegionValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidRegionValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ValidRegionValidator
    {
        return new ValidRegionValidator();
    }

    public function testGetTargets(): void
    {
        $constraint = new ValidRegion();
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testAddressWithoutCountry(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry(null);
        $address->setRegion(null);

        $constraint = new ValidRegion();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testAddressWithoutRegion(): void
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($this->createMock(Country::class));
        $address->setRegion(null);

        $constraint = new ValidRegion();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testValidRegion(): void
    {
        $country = $this->createMock(Country::class);
        $region = $this->createMock(Region::class);
        $regions = $this->createMock(Collection::class);

        $country->expects($this->once())
            ->method('getRegions')
            ->willReturn($regions);
        $regions->expects($this->once())
            ->method('contains')
            ->with($this->identicalTo($region))
            ->willReturn(true);

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegion($region);

        $constraint = new ValidRegion();
        $this->validator->validate($address, $constraint);
        $this->assertNoViolation();
    }

    public function testInvalidRegion(): void
    {
        $country = $this->createMock(Country::class);
        $region = $this->createMock(Region::class);
        $regions = $this->createMock(Collection::class);

        $country->expects($this->once())
            ->method('getRegions')
            ->willReturn($regions);
        $regions->expects($this->once())
            ->method('contains')
            ->with($this->identicalTo($region))
            ->willReturn(false);

        $country->expects($this->once())
            ->method('getName')
            ->willReturn('Country');
        $region->expects($this->once())
            ->method('getName')
            ->willReturn('Region');

        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($country);
        $address->setRegion($region);

        $constraint = new ValidRegion();
        $this->validator->validate($address, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ region }}' => 'Region', '{{ country }}' => 'Country'])
            ->assertRaised();
    }
}
