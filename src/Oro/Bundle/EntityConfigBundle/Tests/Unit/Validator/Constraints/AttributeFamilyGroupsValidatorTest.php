<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroups;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroupsValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class AttributeFamilyGroupsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var AttributeFamilyGroupsValidator
     */
    protected $validator;

    /**
     * @var AttributeFamilyGroups
     */
    protected $constraint;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->getMock();
        $this->validator = new AttributeFamilyGroupsValidator();
        $this->validator->initialize($this->context);
        $this->constraint = new AttributeFamilyGroups();
    }

    public function testValidateWrongEntity()
    {
        $entity = new \stdClass();
        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($entity, $this->constraint);
    }

    public function testValidateEmptyGroups()
    {
        $entity = new AttributeFamily();
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->emptyGroupsMessage);

        $this->validator->validate($entity, $this->constraint);
    }

    public function testValidateSameLabelsConstraint()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroupStub(1, 'default label 1');
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroupStub(2, 'default label 1');
        $entity->addAttributeGroup($group2);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->sameLabelsMessage);

        $this->validator->validate($entity, $this->constraint);
    }

    public function testValidateValid()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroupStub(1, 'default label 1');
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroupStub(2, 'default label 2');
        $entity->addAttributeGroup($group2);

        $this->context->expects($this->never())
            ->method('addViolation');

        $this->validator->validate($entity, $this->constraint);
    }
}
