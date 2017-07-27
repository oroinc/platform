<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\LoadParentResourceMetadata;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class LoadParentResourceMetadataTest extends MetadataProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var LoadParentResourceMetadata */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->metadataProvider = $this->getMockBuilder(MetadataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->assertSame($metadata, $this->context->getResult());
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

        $this->assertFalse($this->context->hasResult());
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
        $expectedMetadata->setHasIdentifierGenerator(false);

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

        $this->assertEquals($parentEntityClass, $config->getParentResourceClass());
        $this->assertEquals($expectedMetadata, $this->context->getResult());
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
