<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributes;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributesValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class GroupAttributesValidatorTest extends ConstraintValidatorTestCase
{
    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        parent::setUp();
    }

    protected function createValidator(): GroupAttributesValidator
    {
        return new GroupAttributesValidator($this->attributeManager);
    }

    private function getFieldConfigModel(int $id): FieldConfigModel
    {
        $model = new FieldConfigModel();
        ReflectionUtil::setId($model, $id);

        return $model;
    }

    public function testGetTargets()
    {
        $constraint = new GroupAttributes();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateWrongEntity()
    {
        $entity = new \stdClass();

        $this->attributeManager->expects($this->never())
            ->method('getSystemAttributesByClass');

        $constraint = new GroupAttributes();
        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateDuplicateAttributesMessage()
    {
        $entity = new AttributeFamily();
        $group1 = new AttributeGroup();
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroup();
        $entity->addAttributeGroup($group2);

        $relation1 = new AttributeGroupRelation();
        $relation1->setEntityConfigFieldId(1);
        $group1->addAttributeRelation($relation1);
        $relation2 = new AttributeGroupRelation();
        $relation2->setEntityConfigFieldId(1);
        $group2->addAttributeRelation($relation2);

        $this->attributeManager->expects($this->never())
            ->method('getSystemAttributesByClass');

        $constraint = new GroupAttributes();
        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->duplicateAttributesMessage)
            ->assertRaised();
    }

    public function testValidateMissingSystemAttributes()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroup();
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroup();
        $entity->addAttributeGroup($group2);

        $relation1 = new AttributeGroupRelation();
        $relation1->setEntityConfigFieldId(1);
        $group1->addAttributeRelation($relation1);
        $relation2 = new AttributeGroupRelation();
        $relation2->setEntityConfigFieldId(2);
        $group2->addAttributeRelation($relation2);

        $systemAttribute = $this->getFieldConfigModel(3);

        $this->attributeManager->expects($this->once())
            ->method('getSystemAttributesByClass')
            ->with($entity->getEntityClass())
            ->willReturn([$systemAttribute]);

        $constraint = new GroupAttributes();
        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->missingSystemAttributesMessage)
            ->assertRaised();
    }

    public function testValidateValid()
    {
        $entity = new AttributeFamily();
        $entity->setEntityClass('entityClass');
        $group1 = new AttributeGroup();
        $entity->addAttributeGroup($group1);
        $group2 = new AttributeGroup();
        $entity->addAttributeGroup($group2);

        $relation1 = new AttributeGroupRelation();
        $relation1->setEntityConfigFieldId(1);
        $group1->addAttributeRelation($relation1);
        $relation2 = new AttributeGroupRelation();
        $relation2->setEntityConfigFieldId(2);
        $group2->addAttributeRelation($relation2);

        $systemAttribute = $this->getFieldConfigModel(2);

        $this->attributeManager->expects($this->once())
            ->method('getSystemAttributesByClass')
            ->with($entity->getEntityClass())
            ->willReturn([$systemAttribute]);

        $constraint = new GroupAttributes();
        $this->validator->validate($entity, $constraint);

        $this->assertNoViolation();
    }
}
