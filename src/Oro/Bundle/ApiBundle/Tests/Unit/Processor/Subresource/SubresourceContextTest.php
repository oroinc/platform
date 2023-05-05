<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SubresourceContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var SubresourceContext */
    private $context;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new SubresourceContext($this->configProvider, $this->metadataProvider);
    }

    private function getConfig(array $data = []): Config
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }

    public function testParentClassName()
    {
        self::assertNull($this->context->getParentClassName());

        $this->context->setParentClassName('test');
        self::assertEquals('test', $this->context->getParentClassName());
        self::assertEquals('test', $this->context->get('parentClass'));

        $this->context->setParentClassName(null);
        self::assertNull($this->context->getParentClassName());
        self::assertFalse($this->context->has('parentClass'));
    }

    public function testParentId()
    {
        self::assertNull($this->context->getParentId());

        $parentId = 'test';
        $this->context->setParentId($parentId);
        self::assertEquals($parentId, $this->context->getParentId());

        $this->context->setParentId(null);
        self::assertNull($this->context->getParentId());
    }

    public function testAssociationName()
    {
        self::assertNull($this->context->getAssociationName());

        $this->context->setAssociationName('test');
        self::assertEquals('test', $this->context->getAssociationName());
        self::assertEquals('test', $this->context->get('association'));

        $this->context->setAssociationName(null);
        self::assertNull($this->context->getAssociationName());
        self::assertFalse($this->context->has('association'));
    }

    public function testIsCollection()
    {
        self::assertFalse($this->context->isCollection());
        self::assertTrue($this->context->has('collection'));
        self::assertFalse($this->context->get('collection'));

        $this->context->setIsCollection(true);
        self::assertTrue($this->context->isCollection());
        self::assertTrue($this->context->get('collection'));
    }

    public function testGetParentConfigExtras()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $expectedParentConfigExtras = [new EntityDefinitionConfigExtra($action)];
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->getParentConfigExtras()
        );
    }

    public function testSetParentConfigExtras()
    {
        $this->context->setParentConfigExtras([new EntityDefinitionConfigExtra('get_list')]);
        self::assertEquals(
            [new EntityDefinitionConfigExtra('get_list')],
            $this->context->getParentConfigExtras()
        );
    }

    public function testSetParentConfigExtrasForHateoas()
    {
        $this->context->setHateoas(true);
        $configExtra = new TestConfigExtra('test');

        $this->context->setParentConfigExtras([$configExtra]);
        self::assertEquals(
            [$configExtra, new HateoasConfigExtra()],
            $this->context->getParentConfigExtras()
        );

        $configExtras = [new HateoasConfigExtra(), $configExtra];
        $this->context->setParentConfigExtras($configExtras);
        self::assertEquals($configExtras, $this->context->getParentConfigExtras());
    }

    public function testRemoveParentConfigExtras()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->context->setParentConfigExtras([]);

        $expectedParentConfigExtras = [new EntityDefinitionConfigExtra($action)];
        self::assertEquals(
            $expectedParentConfigExtras,
            $this->context->getParentConfigExtras()
        );
    }

    public function testSetInvalidParentConfigExtras()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected an array of "Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraInterface".'
        );

        $this->context->setParentConfigExtras(['test']);
    }

    public function testHasParentConfigExtra()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        self::assertTrue($this->context->hasParentConfigExtra(EntityDefinitionConfigExtra::NAME));
        self::assertFalse($this->context->hasParentConfigExtra('another'));
    }

    public function testGetParentConfigExtra()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        self::assertInstanceOf(
            EntityDefinitionConfigExtra::class,
            $this->context->getParentConfigExtra(EntityDefinitionConfigExtra::NAME)
        );
        self::assertNull($this->context->getParentConfigExtra('another'));
    }

    public function testAddAndRemoveParentConfigExtra()
    {
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $extra = new TestConfigExtra('another');
        $this->context->addParentConfigExtra($extra);
        self::assertTrue($this->context->hasParentConfigExtra(EntityDefinitionConfigExtra::NAME));
        self::assertSame($extra, $this->context->getParentConfigExtra($extra->getName()));

        $this->context->removeParentConfigExtra($extra->getName());
        self::assertTrue($this->context->hasParentConfigExtra(EntityDefinitionConfigExtra::NAME));
        self::assertFalse($this->context->hasParentConfigExtra($extra->getName()));
    }

    public function testLoadParentConfig()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [new EntityDefinitionConfigExtra($action)]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasParentConfig());

        self::assertEquals($config, $this->context->getParentConfig()); // load config
        self::assertTrue($this->context->hasParentConfig());
        self::assertTrue($this->context->has('parentConfig'));
        self::assertEquals($config, $this->context->get('parentConfig'));

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getParentConfig());
    }

    public function testLoadParentConfigWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [new EntityDefinitionConfigExtra($action)]
            )
            ->willThrowException($exception);

        // test that a config is not loaded yet
        self::assertFalse($this->context->hasParentConfig());

        try {
            $this->context->getParentConfig(); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasParentConfig());
        self::assertTrue($this->context->has('parentConfig'));
        self::assertNull($this->context->get('parentConfig'));

        // test that a config is loaded only once
        self::assertNull($this->context->getParentConfig());
    }

    public function testLoadParentConfigWhenNoParentClassName()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The parent entity class name must be set in the context before a configuration is loaded.'
        );

        $this->context->getParentConfig();
    }

    public function testParentConfigWhenItIsSetExplicitly()
    {
        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setParentClassName('Test\Class');
        $this->context->setAssociationName('test');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setParentConfig($config);

        self::assertTrue($this->context->hasParentConfig());
        self::assertEquals($config, $this->context->getParentConfig());
        self::assertTrue($this->context->has('parentConfig'));
        self::assertEquals($config, $this->context->get('parentConfig'));

        // test remove config
        $this->context->setParentConfig(null);
        self::assertFalse($this->context->hasParentConfig());
    }

    public function testGetParentMetadataExtras()
    {
        self::assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
    }

    public function testGetParentMetadataExtrasWhenActionExistsInContext()
    {
        $action = 'test_action';
        $this->context->setAction($action);

        self::assertEquals(
            [new ActionMetadataExtra($action)],
            $this->context->getParentMetadataExtras()
        );
    }

    public function testSetParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([new TestMetadataExtra('test')]);
        self::assertEquals(
            [new TestMetadataExtra('test')],
            $this->context->getParentMetadataExtras()
        );
    }

    public function testSetParentMetadataExtrasForHateoas()
    {
        $this->context->setHateoas(true);
        $metadataExtra = new TestMetadataExtra('test');

        $this->context->setParentMetadataExtras([$metadataExtra]);
        self::assertEquals(
            [$metadataExtra, new HateoasMetadataExtra($this->context->getFilterValues())],
            $this->context->getParentMetadataExtras()
        );

        $metadataExtras = [new HateoasMetadataExtra($this->context->getFilterValues()), $metadataExtra];
        $this->context->setParentMetadataExtras($metadataExtras);
        self::assertEquals($metadataExtras, $this->context->getParentMetadataExtras());
    }

    public function testGetParentMetadataExtrasForHateoas()
    {
        $this->context->setHateoas(true);

        self::assertEquals(
            [new HateoasMetadataExtra($this->context->getFilterValues())],
            $this->context->getParentMetadataExtras()
        );
    }

    public function testGetParentMetadataExtrasForNoHateoas()
    {
        self::assertEquals([], $this->context->getParentMetadataExtras());
    }

    public function testRemoveParentMetadataExtras()
    {
        $this->context->setParentMetadataExtras([]);
        self::assertEquals(
            [],
            $this->context->getParentMetadataExtras()
        );
    }

    public function testSetInvalidParentMetadataExtras()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected an array of "Oro\Bundle\ApiBundle\Metadata\Extra\MetadataExtraInterface".'
        );

        $this->context->setParentMetadataExtras(['test']);
    }

    public function testLoadParentMetadata()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [new EntityDefinitionConfigExtra($action)]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasParentMetadata());

        self::assertSame($metadata, $this->context->getParentMetadata()); // load metadata
        self::assertTrue($this->context->hasParentMetadata());
        self::assertTrue($this->context->has('parentMetadata'));
        self::assertSame($metadata, $this->context->get('parentMetadata'));

        self::assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getParentMetadata());
    }

    public function testLoadParentMetadataWhenHateoasIsEnabled()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);
        $this->context->setHateoas(true);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [new EntityDefinitionConfigExtra($action), new HateoasConfigExtra()]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                array_merge($metadataExtras, [new HateoasMetadataExtra($this->context->getFilterValues())])
            )
            ->willReturn($metadata);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasParentMetadata());

        self::assertSame($metadata, $this->context->getParentMetadata()); // load metadata
        self::assertTrue($this->context->hasParentMetadata());
        self::assertTrue($this->context->has('parentMetadata'));
        self::assertSame($metadata, $this->context->get('parentMetadata'));

        self::assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getParentMetadata());
    }

    public function testLoadParentMetadataWhenNoParentClassName()
    {
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->context->getParentMetadata());
        self::assertTrue($this->context->hasParentMetadata());
    }

    public function testLoadParentMetadataWhenExceptionOccurs()
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_relationship';
        $isCollection = true;
        $parentEntityClass = 'Test\Class';
        $associationName = 'test';
        $exception = new \RuntimeException('some error');

        $config = new EntityDefinitionConfig();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setIsCollection($isCollection);
        $this->context->setParentMetadataExtras($metadataExtras);
        $this->context->setParentClassName($parentEntityClass);
        $this->context->setAssociationName($associationName);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                [new EntityDefinitionConfigExtra($action)]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $parentEntityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willThrowException($exception);

        // test that metadata are not loaded yet
        self::assertFalse($this->context->hasParentMetadata());

        try {
            $this->context->getParentMetadata(); // load metadata
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }
        self::assertTrue($this->context->hasParentMetadata());
        self::assertTrue($this->context->has('parentMetadata'));
        self::assertNull($this->context->get('parentMetadata'));

        self::assertEquals($config, $this->context->getParentConfig());

        // test that metadata are loaded only once
        self::assertNull($this->context->getParentMetadata());
    }

    public function testMetadataWhenItIsSetExplicitly()
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->context->setParentMetadata($metadata);

        self::assertTrue($this->context->hasParentMetadata());
        self::assertSame($metadata, $this->context->getParentMetadata());
        self::assertTrue($this->context->has('parentMetadata'));
        self::assertSame($metadata, $this->context->get('parentMetadata'));

        // test remove metadata
        $this->context->setParentMetadata(null);
        self::assertFalse($this->context->hasParentMetadata());
    }

    public function testHateoas()
    {
        self::assertFalse($this->context->isHateoasEnabled());
        self::assertFalse($this->context->get('hateoas'));

        $this->context->setHateoas(true);
        self::assertTrue($this->context->isHateoasEnabled());
        self::assertTrue($this->context->get('hateoas'));

        $this->context->setHateoas(false);
        self::assertFalse($this->context->isHateoasEnabled());
        self::assertFalse($this->context->get('hateoas'));
    }

    public function testHateoasForConfigExtras()
    {
        $this->context->setHateoas(true);
        self::assertEquals([new HateoasConfigExtra()], $this->context->getConfigExtras());

        $this->context->setHateoas(false);
        self::assertEquals([], $this->context->getConfigExtras());
    }

    public function testHateoasForMetadataExtras()
    {
        // make sure that metadata extras are initialized
        $this->context->getMetadataExtras();

        $this->context->setHateoas(true);
        self::assertEquals(
            [new HateoasMetadataExtra($this->context->getFilterValues())],
            $this->context->getMetadataExtras()
        );

        $this->context->setHateoas(false);
        self::assertEquals([], $this->context->getMetadataExtras());
    }

    public function testHateoasForParentConfigExtras()
    {
        $this->context->setAction('action');
        $this->context->setParentClassName('Test\ParentEntity');
        $this->context->setAssociationName('test');

        $this->context->setHateoas(true);
        self::assertEquals(
            [new EntityDefinitionConfigExtra('action'), new HateoasConfigExtra()],
            $this->context->getParentConfigExtras()
        );

        $this->context->setHateoas(false);
        self::assertEquals(
            [new EntityDefinitionConfigExtra('action')],
            $this->context->getParentConfigExtras()
        );
    }

    public function testHateoasForParentMetadataExtras()
    {
        $this->context->setAction('action');

        // make sure that metadata extras are initialized
        $this->context->getParentMetadataExtras();

        $this->context->setHateoas(true);
        self::assertEquals(
            [
                new ActionMetadataExtra('action'),
                new HateoasMetadataExtra($this->context->getFilterValues())
            ],
            $this->context->getParentMetadataExtras()
        );

        $this->context->setHateoas(false);
        self::assertEquals(
            [new ActionMetadataExtra('action')],
            $this->context->getParentMetadataExtras()
        );
    }
}
