<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\DeleteRelationship;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\DataMapper\RemoveRelationshipMapper;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;

class BuildFormBuilderTest extends ChangeRelationshipProcessorTestCase
{
    const TEST_PARENT_CLASS_NAME = 'Test\Entity';
    const TEST_ASSOCIATION_NAME  = 'testAssociation';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    /** @var BuildFormBuilder */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->processor = new BuildFormBuilder(
            new FormHelper($this->formFactory, $this->container),
            $this->propertyAccessor
        );

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testRemoveRelationshipMapperShouldBeSetForFormBuilder()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('setDataMapper')
            ->with($this->isInstanceOf(RemoveRelationshipMapper::class));

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
