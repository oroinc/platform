<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\BusinessUnitOwner;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\BusinessUnitOwnerValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class BusinessUnitOwnerValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new BusinessUnitOwnerValidator();
    }

    private function getBusinessUnit(int $id = null): BusinessUnit
    {
        $businessUnit = new BusinessUnit();
        if (null !== $id) {
            $businessUnit->setId($id);
        }

        return $businessUnit;
    }

    public function testGetTargets()
    {
        $constraint = new BusinessUnitOwner();
        $this->assertEquals(BusinessUnitOwner::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidBusinessUnit()
    {
        $businessUnit = $this->getBusinessUnit(1);
        $businessUnit->setOwner($this->getBusinessUnit(2));

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->assertNoViolation();
    }

    public function testValidNewBothBusinessUnits()
    {
        $businessUnit = $this->getBusinessUnit();
        $businessUnit->setOwner($this->getBusinessUnit());

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->assertNoViolation();
    }

    public function testValidNewBusinessUnit()
    {
        $businessUnit = $this->getBusinessUnit();
        $parentBusinessUnit = $this->getBusinessUnit(1);
        $businessUnit->setOwner($parentBusinessUnit);

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->assertNoViolation();
    }

    public function testValidNewParentBusinessUnit()
    {
        $businessUnit = $this->getBusinessUnit(1);
        $businessUnit->setOwner($this->getBusinessUnit());

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->assertNoViolation();
    }

    public function testInvalidBusinessUnit()
    {
        $businessUnit = $this->getBusinessUnit(1);
        $businessUnit->setOwner($businessUnit);

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testInvalidNewBusinessUnit()
    {
        $businessUnit = $this->getBusinessUnit();
        $businessUnit->setOwner($businessUnit);

        $constraint = new BusinessUnitOwner();
        $this->validator->validate($businessUnit, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
