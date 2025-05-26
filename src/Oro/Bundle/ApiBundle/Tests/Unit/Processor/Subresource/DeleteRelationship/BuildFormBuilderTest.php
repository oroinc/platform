<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\DeleteRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\DataMapper\RemoveRelationshipMapper;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Guesser\DataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\DeleteRelationship\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BuildFormBuilderTest extends ChangeRelationshipProcessorTestCase
{
    private const string TEST_PARENT_CLASS_NAME = 'Test\Entity';
    private const string TEST_ASSOCIATION_NAME = 'testAssociation';

    private FormFactoryInterface&MockObject $formFactory;
    private ContainerInterface&MockObject $container;
    private BuildFormBuilder $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->processor = new BuildFormBuilder(
            new FormHelper(
                $this->formFactory,
                new DataTypeGuesser([]),
                $propertyAccessor,
                $this->container
            ),
            $propertyAccessor
        );

        $this->context->setParentClassName(self::TEST_PARENT_CLASS_NAME);
        $this->context->setAssociationName(self::TEST_ASSOCIATION_NAME);
    }

    public function testRemoveRelationshipMapperShouldBeSetForFormBuilder(): void
    {
        $parentEntity = new \stdClass();
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField(self::TEST_ASSOCIATION_NAME);

        $parentMetadata = new EntityMetadata('Test\Entity');
        $parentMetadata->addAssociation(new AssociationMetadata(self::TEST_ASSOCIATION_NAME));

        $this->formFactory->expects(self::once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder->expects(self::exactly(2))
            ->method('setDataMapper')
            ->withConsecutive(
                [self::isInstanceOf(DataMapper::class)],
                [self::isInstanceOf(RemoveRelationshipMapper::class)]
            );
        $formBuilder->expects(self::once())
            ->method('add')
            ->with(self::TEST_ASSOCIATION_NAME, null, [])
            ->willReturn($this->createMock(FormBuilderInterface::class));

        $this->context->setParentConfig($parentConfig);
        $this->context->setParentMetadata($parentMetadata);
        $this->context->setParentEntity($parentEntity);
        $this->processor->process($this->context);
        self::assertSame($formBuilder, $this->context->getFormBuilder());
    }
}
