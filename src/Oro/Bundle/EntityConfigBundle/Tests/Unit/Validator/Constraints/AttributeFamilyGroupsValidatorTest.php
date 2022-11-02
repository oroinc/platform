<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroups;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\AttributeFamilyGroupsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeFamilyGroupsValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): AttributeFamilyGroupsValidator
    {
        return new AttributeFamilyGroupsValidator();
    }

    public function testGetTargets()
    {
        $constraint = new AttributeFamilyGroups();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWrongEntity()
    {
        $entity = new \stdClass();

        $constraint = new AttributeFamilyGroups();
        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateEmptyGroups()
    {
        $entity = new AttributeFamily();

        $constraint = new AttributeFamilyGroups();
        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->emptyGroupsMessage)
            ->assertRaised();
    }

    public function testValidateSameLabelsConstraint()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroupStub(1, 'default label 1');
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroupStub(2, 'default label 1');
        $entity->addAttributeGroup($group2);

        $constraint = new AttributeFamilyGroups();
        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->sameLabelsMessage)
            ->assertRaised();
    }

    public function testValidateValid()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroupStub(1, 'default label 1');
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroupStub(2, 'default label 2');
        $entity->addAttributeGroup($group2);

        $constraint = new AttributeFamilyGroups();
        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }
}
