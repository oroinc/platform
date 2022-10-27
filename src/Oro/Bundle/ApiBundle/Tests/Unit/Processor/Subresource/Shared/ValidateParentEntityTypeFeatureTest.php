<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\ValidateParentEntityTypeFeature;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidateParentEntityTypeFeatureTest extends GetSubresourceProcessorTestCase
{
    /** @var ResourcesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesProvider;

    /** @var ValidateParentEntityTypeFeature */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new ValidateParentEntityTypeFeature($this->resourcesProvider);
    }

    public function testProcessDisabledParentEntity(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $parentClassName = 'Test\Class';
        $associationName = 'testAssociation';
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata = new AssociationMetadata($associationName);
        $parentMetadata->addAssociation($associationMetadata);

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(false);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessEnabledParentEntity(): void
    {
        $parentClassName = 'Test\Class';
        $associationName = 'testAssociation';
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata = new AssociationMetadata($associationName);
        $parentMetadata->addAssociation($associationMetadata);

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenNoParentMetadata(): void
    {
        $parentClassName = 'Test\Class';
        $config = new Config();
        $config->setDefinition(new EntityDefinitionConfig());

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->willReturn(null);
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('testAssociation');
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenParentMetadataContainsAssociation(): void
    {
        $parentClassName = 'Test\Class';
        $associationName = 'testAssociation';
        $parentMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata = new AssociationMetadata($associationName);
        $parentMetadata->addAssociation($associationMetadata);

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata($parentMetadata);
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenParentMetadataDoesNotContainAssociationAndNoParentConfig(): void
    {
        $parentClassName = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->willReturn(new Config());

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('testAssociation');
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenParentMetadataAndParentConfigDoNotContainAssociation(): void
    {
        $parentClassName = 'Test\Class';

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName('testAssociation');
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenParentMetadataDoesNotContainAssociationAndAssociationNotExcluded(): void
    {
        $parentClassName = 'Test\Class';
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName);

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));
        $this->context->setParentConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);
    }

    public function testProcessAssociationWhenParentMetadataDoesNotContainAssociationAndAssociationExcluded(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $parentClassName = 'Test\Class';
        $associationName = 'testAssociation';
        $parentConfig = new EntityDefinitionConfig();
        $parentConfig->addField($associationName)->setExcluded();

        $this->resourcesProvider->expects(self::once())
            ->method('isResourceEnabled')
            ->with(
                $parentClassName,
                $this->context->getAction(),
                $this->context->getVersion(),
                $this->context->getRequestType()
            )
            ->willReturn(true);

        $this->context->setParentClassName($parentClassName);
        $this->context->setAssociationName($associationName);
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));
        $this->context->setParentConfig($parentConfig);
        $this->processor->process($this->context);
    }
}
