<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributes;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\GroupAttributesValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class GroupAttributesValidatorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeManager;

    /**
     * @var GroupAttributesValidator
     */
    protected $validator;

    /**
     * @var GroupAttributes
     */
    protected $constraint;

    protected function setUp()
    {
        $this->context = $this->getMockBuilder(ExecutionContextInterface::class)
            ->getMock();
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = new GroupAttributesValidator($this->attributeManager);
        $this->validator->initialize($this->context);
        $this->constraint = new GroupAttributes();
    }

    public function testValidateWrongEntity()
    {
        $entity = new \stdClass();
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->attributeManager->expects($this->never())
            ->method('getSystemAttributesByClass');

        $this->validator->validate($entity, $this->constraint);
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

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->duplicateAttributesMessage);

        $this->attributeManager->expects($this->never())
            ->method('getSystemAttributesByClass');

        $this->validator->validate($entity, $this->constraint);
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
        
        $systemAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 3]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->missingSystemAttributesMessage);

        $this->attributeManager->expects($this->once())
            ->method('getSystemAttributesByClass')
            ->with($entity->getEntityClass())
            ->willReturn([$systemAttribute]);

        $this->validator->validate($entity, $this->constraint);
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

        $systemAttribute = $this->getEntity(FieldConfigModel::class, ['id' => 2]);

        $this->context->expects($this->never())
            ->method('addViolation');
        $this->attributeManager->expects($this->once())
            ->method('getSystemAttributesByClass')
            ->with($entity->getEntityClass())
            ->willReturn([$systemAttribute]);

        $this->validator->validate($entity, $this->constraint);
    }
}
