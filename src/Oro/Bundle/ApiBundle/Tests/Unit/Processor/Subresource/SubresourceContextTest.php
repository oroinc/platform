<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourceContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var SubresourceContext */
    protected $context;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new SubresourceContext($this->configProvider, $this->metadataProvider);
    }

    /**
     * @param array $data
     *
     * @return Config
     */
    protected function getConfig(array $data = [])
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }

    public function testParentClassName()
    {
        $this->assertNull($this->context->getParentClassName());

        $this->context->setParentClassName('test');
        $this->assertEquals('test', $this->context->getParentClassName());
        $this->assertEquals('test', $this->context->get(SubresourceContext::PARENT_CLASS_NAME));
    }

    public function testParentId()
    {
        $this->assertNull($this->context->getParentId());

        $this->context->setParentId('test');
        $this->assertEquals('test', $this->context->getParentId());
        $this->assertEquals('test', $this->context->get(SubresourceContext::PARENT_ID));
    }

    public function testAssociationName()
    {
        $this->assertNull($this->context->getAssociationName());

        $this->context->setAssociationName('test');
        $this->assertEquals('test', $this->context->getAssociationName());
        $this->assertEquals('test', $this->context->get(SubresourceContext::ASSOCIATION));
    }

    public function testIsCollection()
    {
        $this->assertFalse($this->context->isCollection());
        $this->assertTrue($this->context->has(SubresourceContext::COLLECTION));
        $this->assertFalse($this->context->get(SubresourceContext::COLLECTION));

        $this->context->setIsCollection(true);
        $this->assertTrue($this->context->isCollection());
        $this->assertTrue($this->context->get(SubresourceContext::COLLECTION));
    }

    public function testParentEntity()
    {
        $this->assertNull($this->context->getParentEntity());
        $this->assertFalse($this->context->hasParentEntity());

        $entity = new \stdClass();
        $this->context->setParentEntity($entity);
        $this->assertSame($entity, $this->context->getParentEntity());
        $this->assertSame($entity, $this->context->get(SubresourceContext::PARENT_ENTITY));
        $this->assertTrue($this->context->hasParentEntity());

        $this->context->setParentEntity(null);
        $this->assertNull($this->context->getParentEntity());
        $this->assertTrue($this->context->hasParentEntity());
    }

    public function testGetParentConfigExtras()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->assertNull($this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS));

        $this->assertEquals(
            [
                new EntityDefinitionConfigExtra(),
                new FilterFieldsConfigExtra(
                    [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                )
            ],
            $this->context->getParentConfigExtras()
        );
        $this->assertEquals(
            [
                new EntityDefinitionConfigExtra(),
                new FilterFieldsConfigExtra(
                    [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                )
            ],
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    public function testSetParentConfigExtras()
    {
        $this->context->setParentConfigExtras([new EntityDefinitionConfigExtra('get_list')]);
        $this->assertEquals(
            [new EntityDefinitionConfigExtra('get_list')],
            $this->context->getParentConfigExtras()
        );
        $this->assertEquals(
            [new EntityDefinitionConfigExtra('get_list')],
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    public function testRemoveParentConfigExtras()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->context->setParentConfigExtras([]);
        $this->assertNull($this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS));
        $this->assertEquals(
            [
                new EntityDefinitionConfigExtra(),
                new FilterFieldsConfigExtra(
                    [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                )
            ],
            $this->context->getParentConfigExtras()
        );
        $this->assertEquals(
            [
                new EntityDefinitionConfigExtra(),
                new FilterFieldsConfigExtra(
                    [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                )
            ],
            $this->context->get(SubresourceContext::PARENT_CONFIG_EXTRAS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Config\ConfigExtraInterface".
     */
    public function testSetInvalidParentConfigExtras()
    {
        $this->context->setParentConfigExtras(['test']);
    }

    public function testLoadParentConfig()
    {
        $version = '1.1';
        $requestType = 'rest';
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasParentConfig());

        $this->assertEquals($config, $this->context->getParentConfig()); // load config
        $this->assertTrue($this->context->hasParentConfig());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        $this->assertEquals($config, $this->context->get(SubresourceContext::PARENT_CONFIG));

        // test that a config is loaded only once
        $this->assertEquals($config, $this->context->getParentConfig());
    }

    public function testLoadParentConfigWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willThrowException($exception);

        // test that a config is not loaded yet
        $this->assertFalse($this->context->hasParentConfig());

        try {
            $this->context->getParentConfig(); // load config
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
        $this->assertTrue($this->context->hasParentConfig());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        $this->assertNull($this->context->get(SubresourceContext::PARENT_CONFIG));

        // test that a config is loaded only once
        $this->assertNull($this->context->getParentConfig());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The parent entity class name must be set in the context before a configuration is loaded.
     */
    // @codingStandardsIgnoreEnd
    public function testLoadParentConfigWhenNoParentClassName()
    {
        $this->context->getParentConfig();
    }

    public function testParentConfigWhenItIsSetExplicitly()
    {
        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->context->setParentConfig($config);

        $this->assertTrue($this->context->hasParentConfig());
        $this->assertEquals($config, $this->context->getParentConfig());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_CONFIG));
        $this->assertEquals($config, $this->context->get(SubresourceContext::PARENT_CONFIG));

        // test remove config
        $this->context->setParentConfig();
        $this->assertFalse($this->context->hasParentConfig());
    }

    public function testGetParentMetadataExtras()
    {
        $this->assertNull($this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS));

        $this->assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
        $this->assertEquals(
            [],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );

    }

    public function testSetParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([new TestMetadataExtra('test')]);
        $this->assertEquals(
            [new TestMetadataExtra('test')],
            $this->context->getParentMetadataExtras()
        );
        $this->assertEquals(
            [new TestMetadataExtra('test')],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    public function testRemoveParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([]);
        $this->assertNull($this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS));
        $this->assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
        $this->assertEquals(
            [],
            $this->context->get(SubresourceContext::PARENT_METADATA_EXTRAS)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected an array of "Oro\Bundle\ApiBundle\Metadata\MetadataExtraInterface".
     */
    public function testSetInvalidParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras(['test']);
    }

    public function testLoadParentMetadata()
    {
        $version = '1.1';
        $requestType = 'rest';
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $metadataExtras,
                $config
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        $this->assertFalse($this->context->hasParentMetadata());

        $this->assertSame($metadata, $this->context->getParentMetadata()); // load metadata
        $this->assertTrue($this->context->hasParentMetadata());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        $this->assertSame($metadata, $this->context->get(SubresourceContext::PARENT_METADATA));

        $this->assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        $this->assertSame($metadata, $this->context->getParentMetadata());
    }

    public function testLoadParentMetadataWhenNoParentClassName()
    {
        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->assertNull($this->context->getParentMetadata());
        $this->assertTrue($this->context->hasParentMetadata());
    }

    public function testLoadParentMetadataWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [
                    new EntityDefinitionConfigExtra(),
                    new FilterFieldsConfigExtra(
                        [$this->context->getParentClassName() => [$this->context->getAssociationName()]]
                    )
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $metadataExtras,
                $config
            )
            ->willThrowException($exception);

        // test that metadata are not loaded yet
        $this->assertFalse($this->context->hasParentMetadata());

        try {
            $this->context->getParentMetadata(); // load metadata
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
        $this->assertTrue($this->context->hasParentMetadata());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        $this->assertNull($this->context->get(SubresourceContext::PARENT_METADATA));

        $this->assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        $this->assertNull($this->context->getParentMetadata());
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->metadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->context->setParentMetadata($metadata);

        $this->assertTrue($this->context->hasParentMetadata());
        $this->assertSame($metadata, $this->context->getParentMetadata());
        $this->assertTrue($this->context->has(SubresourceContext::PARENT_METADATA));
        $this->assertSame($metadata, $this->context->get(SubresourceContext::PARENT_METADATA));

        // test remove metadata
        $this->context->setParentMetadata();
        $this->assertFalse($this->context->hasParentMetadata());
    }
}
