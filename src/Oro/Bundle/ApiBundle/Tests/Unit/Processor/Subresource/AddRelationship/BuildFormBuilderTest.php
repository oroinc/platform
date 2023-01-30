<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\AddRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Form\DataMapper\AppendRelationshipMapper;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Guesser\DataTypeGuesser;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\AddRelationship\BuildFormBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeRelationshipProcessorTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BuildFormBuilderTest extends ChangeRelationshipProcessorTestCase
{
    private const TEST_PARENT_CLASS_NAME = 'Test\Entity';
    private const TEST_ASSOCIATION_NAME = 'testAssociation';

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var BuildFormBuilder */
    private $processor;

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

    public function testAppendRelationshipMapperShouldBeSetForFormBuilder()
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
                [self::isInstanceOf(PropertyPathMapper::class)],
                [self::isInstanceOf(AppendRelationshipMapper::class)]
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
