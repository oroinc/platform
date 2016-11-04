<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\AddRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\SaveInverseRelations;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class SaveInverseRelationsTest extends ChangeRelationshipTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var SaveInverseRelations */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->processor = new SaveInverseRelations($this->doctrineHelper, $this->propertyAccessor);
    }

    public function testProcessWithoutForm()
    {
        $this->doctrineHelper->expects($this->never())->method('getEntityManager');

        $this->processor->process($this->context);
    }

    public function testProcessWithoutAssociationConfigInContext()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])->getForm();
        $parentEntityClass = Product::class;
        $parentConfig = new EntityDefinitionConfig();

        $this->context->setForm($form);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);

        $this->em->expects($this->never())->method('flush');

        $this->processor->process($this->context);
    }

    public function testProcessWithoutAssociationFieldParameterInFieldConfig()
    {
        $associationName = 'owners_association';
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])->getForm();
        $parentEntityClass = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);

        $this->em->expects($this->never())->method('flush');

        $this->processor->process($this->context);
    }

    public function testProcessWithFormWithoutInverseAssociationField()
    {
        $associationName = 'owners_association';
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])->getForm();
        $parentEntityClass = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)
            ->set('association-field', 'owner');

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);
        $this->context->setClassName('Non/Supported/Class');

        $this->em->expects($this->never())->method('flush');

        $this->processor->process($this->context);
    }

    public function testProcessWithNonSupportedAssociationSource()
    {
        $associationName = 'owners_association';
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add($associationName)
            ->getForm();
        $parentEntityClass = Product::class;
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)
            ->set('association-field', 'owner');

        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);
        $this->context->setClassName('Non/Supported/Class');

        $this->doctrineHelper->expects($this->once())->method('getEntityManager')
            ->with('Non/Supported/Class')
            ->willReturn(null);

        $this->em->expects($this->never())->method('flush');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $className = Product::class;
        $parentEntityClass = User::class;

        $object = new Product();
        $parentEntity = new User();

        $associationName = 'owners_association';

        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add($associationName)
            ->getForm();
        $form->submit(
            [
                $associationName => [
                    $object
                ]
            ]
        );

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)
            ->set('association-field', 'owner');

        $this->context->setClassName($className);
        $this->context->setAssociationName($associationName);
        $this->context->setForm($form);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setParentConfig($parentConfig);
        $this->context->setParentEntity($parentEntity);

        $this->doctrineHelper->expects($this->once())->method('getEntityManager')
            ->with($className)
            ->willReturn($this->em);
        $this->em->expects($this->once())
            ->method('persist')
            ->with($object);
        $this->em->expects($this->once())->method('flush');

        $this->processor->process($this->context);

        $this->assertSame($parentEntity, $object->getOwner());
    }
}
