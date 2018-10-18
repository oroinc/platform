<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\LoadParentResourceMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class LoadParentResourceMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var LoadParentResourceMetadata */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->metadataProvider = $this->createMock(MetadataProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->processor = new LoadParentResourceMetadata(
            $this->metadataProvider,
            $this->doctrineHelper
        );
    }

    public function testProcessForAlreadyLoadedMetadata()
    {
        $metadata = new EntityMetadata();

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');
        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setResult($metadata);
        $this->processor->process($this->context);

        self::assertSame($metadata, $this->context->getResult());
    }

    public function testProcessWhenResourceIsNotBasedOnAnotherResource()
    {
        $entityClass = 'Test\Entity';
        $config = new EntityDefinitionConfig();

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');
        $this->doctrineHelper->expects(self::never())
            ->method('isManageableEntityClass');

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenResourceIsBasedOnAnotherResource()
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\ParentEntity';
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);

        $expectedConfig = new EntityDefinitionConfig();
        $expectedConfig->setParentResourceClass(null);

        $parentMetadata = new EntityMetadata();
        $parentMetadata->setClassName($parentEntityClass);
        $parentMetadata->setHasIdentifierGenerator(true);

        $expectedMetadata = new EntityMetadata();
        $expectedMetadata->setClassName($entityClass);
        $expectedMetadata->setHasIdentifierGenerator(true);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(false);

        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $this->context->getVersion(),
                self::identicalTo($this->context->getRequestType()),
                $expectedConfig,
                $this->context->getExtras()
            )
            ->willReturn($parentMetadata);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);

        self::assertEquals($parentEntityClass, $config->getParentResourceClass());
        self::assertEquals($expectedMetadata, $this->context->getResult());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The class "Test\Entity" must not be a manageable entity because it is based on another API resource. Parent resource is "Test\ParentEntity".
     */
    // @codingStandardsIgnoreEnd
    public function testProcessWhenResourceIsBasedOnAnotherResourceButEntityIsManageable()
    {
        $entityClass = 'Test\Entity';
        $parentEntityClass = 'Test\ParentEntity';
        $config = new EntityDefinitionConfig();
        $config->setParentResourceClass($parentEntityClass);

        $this->doctrineHelper->expects(self::once())
            ->method('isManageableEntityClass')
            ->with($entityClass)
            ->willReturn(true);

        $this->context->setClassName($entityClass);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
