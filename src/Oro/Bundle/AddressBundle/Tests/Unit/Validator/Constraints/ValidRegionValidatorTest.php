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
    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        return new ValidRegionValidator();
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new ValidRegion();
        $this->propertyPath = null;

        return parent::createContext();
    }

    public function testConfiguration()
    {
        $this->assertEquals(ValidRegionValidator::class, $this->constraint->validatedBy());
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

    public function testAddressWithoutRegion()
    {
        $address = $this->getMockForAbstractClass(AbstractAddress::class);
        $address->setCountry($this->createMock(Country::class));
        $address->setRegion(null);
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testValidRegion()
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
        $this->validator->validate($address, $this->constraint);
        $this->assertNoViolation();
    }

    public function testInvalidRegion()
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
        $this->validator->validate($address, $this->constraint);
        $this
            ->buildViolation($this->constraint->message)
            ->setParameters(['{{ region }}' => 'Region', '{{ country }}' => 'Country'])
            ->atPath(null)
            ->assertRaised();
    }
}
