<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\DeleteRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
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
    protected $propertyAccessor;

    /** @var BuildFormBuilder */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->formFactory = $this->createMock('Symfony\Component\Form\FormFactoryInterface');
        $this->propertyAccessor = $this->createMock('Symfony\Component\PropertyAccess\PropertyAccessorInterface');

        $this->processor = new BuildFormBuilder(new FormHelper($this->formFactory), $this->propertyAccessor);

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testRemoveRelationshipMapperShouldBeSetForFormBuilder()
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder->expects($this->once())
            ->method('setDataMapper')
            ->with($this->isInstanceOf('Oro\Bundle\ApiBundle\Form\DataMapper\RemoveRelationshipMapper'));

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        $this->assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
