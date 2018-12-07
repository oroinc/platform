<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\ParentBusinessUnit;
use Oro\Bundle\OrganizationBundle\Validator\Constraints\ParentBusinessUnitValidator;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ParentBusinessUnitValidatorTest extends ConstraintValidatorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $ownerTree;

    protected function setUp()
    {
        $this->ownerTree = $this->createMock(OwnerTreeInterface::class);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function createValidator()
    {
        $ownerTreeProvider = $this->createMock(OwnerTreeProviderInterface::class);
        $ownerTreeProvider->expects($this->any())
            ->method('getTree')
            ->willReturn($this->ownerTree);

        return new ParentBusinessUnitValidator($ownerTreeProvider);
    }

    public function testValidateWithEmptyOwnerBusinessUnit()
    {
        $entity = new BusinessUnit();
        $constraint = new ParentBusinessUnit();

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateValidOwnerBusinessUnit()
    {
        $entity = new BusinessUnit();
        $entity->setId(1);
        $parentBusinessUnit = new BusinessUnit();
        $parentBusinessUnit->setId(5);
        $entity->setParentBusinessUnit($parentBusinessUnit);

        $this->ownerTree->expects($this->once())
            ->method('getSubordinateBusinessUnitIds')
            ->with(1)
            ->willReturn([4, 6, 7]);


        $constraint = new ParentBusinessUnit();

        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateSubordinateOwnerBusinessUnit()
    {
        $entity = new BusinessUnit();
        $entity->setId(1);
        $parentBusinessUnit = new BusinessUnit();
        $parentBusinessUnit->setId(5);
        $entity->setParentBusinessUnit($parentBusinessUnit);

        $this->ownerTree->expects($this->once())
            ->method('getSubordinateBusinessUnitIds')
            ->with(1)
            ->willReturn([4, 5, 6, 7]);


        $constraint = new ParentBusinessUnit();

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }
}
