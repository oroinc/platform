<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\BusinessUnitOwner;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\BusinessUnitOwnerValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BusinessUnitOwnerValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BusinessUnitOwnerValidator
     */
    protected $businessUnitOwnerValidator;

    protected $constraint;

    protected $context;

    /**
     * @var BusinessUnit
     */
    protected $businessUnit;

    protected function setUp()
    {
        $this->businessUnit = new BusinessUnit();

        $this->constraint = new BusinessUnitOwner();
        $this->context = $this->getMockForAbstractClass(ExecutionContextInterface::class);

        $this->businessUnitOwnerValidator = new BusinessUnitOwnerValidator();
        $this->businessUnitOwnerValidator->initialize($this->context);
    }

    public function testValidBusinessUnit()
    {
        $this->businessUnit->setId(1);
        $parentBusinessUnit = new BusinessUnit();
        $parentBusinessUnit->setId(2);
        $this->businessUnit->setOwner($parentBusinessUnit);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }

    public function testValidNewBothBusinessUnits()
    {
        $parentBusinessUnit = new BusinessUnit();
        $this->businessUnit->setOwner($parentBusinessUnit);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }

    public function testValidNewBusinessUnit()
    {
        $parentBusinessUnit = new BusinessUnit();
        $parentBusinessUnit->setId(1);
        $this->businessUnit->setOwner($parentBusinessUnit);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }

    public function testValidNewParentBusinessUnit()
    {
        $this->businessUnit->setId(1);
        $parentBusinessUnit = new BusinessUnit();
        $this->businessUnit->setOwner($parentBusinessUnit);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }

    public function testInvalidBusinessUnit()
    {
        $this->businessUnit->setId(1);
        $this->businessUnit->setOwner($this->businessUnit);

        $this->context->expects($this->once())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }

    public function testInvalidNewBusinessUnit()
    {
        $this->businessUnit->setOwner($this->businessUnit);

        $this->context->expects($this->once())
            ->method('addViolation');

        $this->businessUnitOwnerValidator->validate($this->businessUnit, $this->constraint);
    }
}
