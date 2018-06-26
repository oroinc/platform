<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ActionProcessorInterface;

class MetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ActionProcessorInterface */
    private $processor;

    /** @var MetadataProvider */
    private $metadataProvider;

    protected function setUp()
    {
        $this->processor = $this->createMock(ActionProcessorInterface::class);

        $this->metadataProvider = new MetadataProvider($this->processor);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $className must not be empty.
     */
    public function testShouldThrowExceptionIfClassNameIsEmpty()
    {
        $this->metadataProvider->getMetadata('', '1.2', new RequestType([]), new EntityDefinitionConfig());
    }

    public function testShouldBuildMetadata()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $withExcludedProperties = true;
        $config = new EntityDefinitionConfig();
        $config->setKey('test_config');

        $extra = $this->createMock(MetadataExtraInterface::class);
        $extra->expects(self::any())
            ->method('getName')
            ->willReturn('test_extra');
        $extra->expects(self::any())
            ->method('getCacheKeyPart')
            ->willReturn('test_extra_key');

        $context = new MetadataContext();
        $metadata = new EntityMetadata();

        $this->processor->expects(self::once())
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (MetadataContext $context) use (
                $className,
                $version,
                $requestType,
                $extra,
                $config,
                $withExcludedProperties,
                $metadata
            ) {
                self::assertEquals($className, $context->getClassName());
                self::assertEquals($version, $context->getVersion());
                self::assertEquals($requestType->toArray(), $context->getRequestType()->toArray());
                self::assertSame($config, $context->getConfig());
                self::assertSame($withExcludedProperties, $context->getWithExcludedProperties());
                $extras = $context->getExtras();
                self::assertCount(1, $extras);
                self::assertSame($extra, $extras[0]);

                $metadata->addField(new FieldMetadata('test_field'));
                $context->setResult($metadata);
            });

        $result = $this->metadataProvider->getMetadata(
            $className,
            $version,
            $requestType,
            $config,
            [$extra],
            $withExcludedProperties
        );
        self::assertInstanceOf(EntityMetadata::class, $result);
        self::assertTrue($metadata->hasField('test_field'));
        // a clone of metadata should be returned
        self::assertNotSame($metadata, $result);
        self::assertEquals($metadata, $result);

        // test that the metadata is cached, but its clone should be returned
        $anotherResult = $this->metadataProvider->getMetadata(
            $className,
            $version,
            $requestType,
            $config,
            [$extra],
            $withExcludedProperties
        );
        self::assertNotSame($result, $anotherResult);
        self::assertEquals($result, $anotherResult);
    }

    public function testShouldReturnNullIfMetadataDoesNotExist()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $config = new EntityDefinitionConfig();
        $config->setKey('test_config');
        $context = new MetadataContext();

        $this->processor->expects(self::once())
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        $result = $this->metadataProvider->getMetadata($className, $version, $requestType, $config);
        self::assertNull($result);

        // test that the metadata is cached even if does not exist
        $anotherResult = $this->metadataProvider->getMetadata($className, $version, $requestType, $config);
        self::assertNull($anotherResult);
    }

    public function testShouldNotCacheMetadataIfConfigDoesNotContainKey()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $config = new EntityDefinitionConfig();
        $context = new MetadataContext();

        $this->processor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->with(self::identicalTo($context));

        $this->metadataProvider->getMetadata($className, $version, $requestType, $config);
        $this->metadataProvider->getMetadata($className, $version, $requestType, $config);
    }

    public function testShouldBePossibleToClearInternalCache()
    {
        $className = 'Test\Class';
        $version = '1.2';
        $requestType = new RequestType(['test_request']);
        $config = new EntityDefinitionConfig();
        $config->setKey('test_config');
        $context = new MetadataContext();

        $this->processor->expects(self::exactly(2))
            ->method('createContext')
            ->willReturn($context);
        $this->processor->expects(self::exactly(2))
            ->method('process')
            ->with(self::identicalTo($context));

        $this->metadataProvider->getMetadata($className, $version, $requestType, $config);

        $this->metadataProvider->clearCache();
        $this->metadataProvider->getMetadata($className, $version, $requestType, $config);
    }
}
