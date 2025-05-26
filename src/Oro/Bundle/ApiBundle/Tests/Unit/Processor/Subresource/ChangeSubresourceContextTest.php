<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\HateoasConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestMetadataExtra;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ChangeSubresourceContextTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private MetadataProvider&MockObject $metadataProvider;
    private ChangeSubresourceContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new ChangeSubresourceContext($this->configProvider, $this->metadataProvider);
    }

    private function getConfig(array $data = []): Config
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }

    public function testInitialExisting(): void
    {
        self::assertTrue($this->context->isExisting());
    }

    public function testGetRequestClassName(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $isCollection = true;
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'test';
        $requestEntityClass = 'Test\RequestClass';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

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
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new FilterFieldsConfigExtra([$parentEntityClass => [$associationName]])
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $parentEntityConfig]));

        self::assertEquals($requestEntityClass, $this->context->getRequestClassName());

        // test that the parent config is loaded only once
        self::assertEquals($requestEntityClass, $this->context->getRequestClassName());
    }

    public function testGetRequestClassNameWhenItIsNotConfigured(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $isCollection = true;
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'test';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();

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
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new FilterFieldsConfigExtra([$parentEntityClass => [$associationName]])
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $parentEntityConfig]));

        self::assertNull($this->context->getRequestClassName());

        // test that the parent config is loaded only once
        self::assertNull($this->context->getRequestClassName());
    }

    public function testSetRequestClassName(): void
    {
        $requestEntityClass = 'Test\RequestClass';

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setRequestClassName($requestEntityClass);

        self::assertEquals($requestEntityClass, $this->context->getRequestClassName());
    }

    public function testGetRequestDocumentationAction(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $isCollection = true;
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'test';
        $requestDocumentationAction = 'request_action';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_DOCUMENTATION_ACTION, $requestDocumentationAction);

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
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new FilterFieldsConfigExtra([$parentEntityClass => [$associationName]])
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $parentEntityConfig]));

        self::assertEquals($requestDocumentationAction, $this->context->getRequestDocumentationAction());

        // test that the parent config is loaded only once
        self::assertEquals($requestDocumentationAction, $this->context->getRequestDocumentationAction());
    }

    public function testGetRequestDocumentationActionWhenItIsNotConfigured(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $isCollection = true;
        $parentEntityClass = 'Test\ParentClass';
        $associationName = 'test';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();

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
                [
                    new EntityDefinitionConfigExtra($action, $isCollection, $parentEntityClass, $associationName),
                    new FilterFieldsConfigExtra([$parentEntityClass => [$associationName]])
                ]
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $parentEntityConfig]));

        self::assertNull($this->context->getRequestDocumentationAction());

        // test that the parent config is loaded only once
        self::assertNull($this->context->getRequestDocumentationAction());
    }

    public function testSetRequestDocumentationAction(): void
    {
        $requestDocumentationAction = 'request_action';

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setRequestDocumentationAction($requestDocumentationAction);

        self::assertEquals($requestDocumentationAction, $this->context->getRequestDocumentationAction());
    }

    public function testLoadRequestConfigWithRequestClassName(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $entityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig()); // load config

        // test that a config is loaded only once
        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());
    }

    public function testLoadRequestConfigWithRequestClassNameAndRequestDocumentationAction(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';
        $requestDocumentationAction = 'request_action';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);
        $parentEntityConfig->set(ConfigUtil::REQUEST_DOCUMENTATION_ACTION, $requestDocumentationAction);

        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action), new DescriptionsConfigExtra()];
        $requestEntityConfigExtras = [$entityConfigExtras[0], new DescriptionsConfigExtra($requestDocumentationAction)];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $requestEntityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig()); // load config

        // test that a config is loaded only once
        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());
    }

    public function testLoadRequestConfigWithoutRequestClassNameAndWithRequestDocumentationAction(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestDocumentationAction = 'request_action';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_DOCUMENTATION_ACTION, $requestDocumentationAction);

        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action), new DescriptionsConfigExtra()];
        $requestEntityConfigExtras = [$entityConfigExtras[0], new DescriptionsConfigExtra($requestDocumentationAction)];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Class',
                $version,
                new RequestType([$requestType]),
                $requestEntityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig()); // load config

        // test that a config is loaded only once
        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());
    }

    public function testLoadRequestConfigWithRequestDocumentationActionAndWithoutDescriptionsConfigExtra(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestDocumentationAction = 'request_action';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_DOCUMENTATION_ACTION, $requestDocumentationAction);

        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                'Test\Class',
                $version,
                new RequestType([$requestType]),
                $entityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig()); // load config

        // test that a config is loaded only once
        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());
    }

    public function testLoadRequestConfigWhenExceptionOccurs(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';
        $exception = new \RuntimeException('some error');

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfig = new EntityDefinitionConfig();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfig($entityConfig);
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $entityConfigExtras
            )
            ->willThrowException($exception);

        try {
            $this->context->getRequestConfig(); // load config
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }

        // test that a config is loaded only once
        self::assertNull($this->context->getRequestConfig());
    }

    public function testLoadRequestConfigWhenNoRequestClassName(): void
    {
        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $entityConfig = new EntityDefinitionConfig();

        $this->context->setParentConfig($parentEntityConfig);
        $this->context->setConfig($entityConfig);

        self::assertSame($entityConfig, $this->context->getRequestConfig()); // load config

        // test that a config is loaded only once
        self::assertSame($entityConfig, $this->context->getRequestConfig());
    }

    public function testRequestConfigWhenItIsSetExplicitly(): void
    {
        $requestEntityConfig = new EntityDefinitionConfig();
        $requestEntityConfig->setExcludeAll();
        $entityConfig = new EntityDefinitionConfig();

        $this->context->setConfig($entityConfig);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setRequestConfig($requestEntityConfig);
        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());

        // test remove config
        $this->context->setRequestConfig(null);
        self::assertSame($entityConfig, $this->context->getRequestConfig());

        // test no entity config
        $this->context->setConfig(null);
        self::assertNull($this->context->getRequestConfig());
    }

    public function testLoadRequestMetadata(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

        $requestEntityConfig = new EntityDefinitionConfig();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];
        $requestEntityMetadata = new EntityMetadata($requestEntityClass);
        $entityMetadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setMetadataExtras($entityMetadataExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $entityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $requestEntityConfig,
                array_merge($entityMetadataExtras, [new ActionMetadataExtra($action)])
            )
            ->willReturn($requestEntityMetadata);

        self::assertSame($requestEntityMetadata, $this->context->getRequestMetadata()); // load metadata

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());

        // test that metadata are loaded only once
        self::assertSame($requestEntityMetadata, $this->context->getRequestMetadata());
    }

    public function testLoadRequestMetadataWhenHateoasIsEnabled(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

        $requestEntityConfig = new EntityDefinitionConfig();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];
        $requestEntityMetadata = new EntityMetadata($requestEntityClass);
        $entityMetadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setMetadataExtras($entityMetadataExtras);
        $this->context->setParentConfig($parentEntityConfig);
        $this->context->setHateoas(true);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                array_merge($entityConfigExtras, [new HateoasConfigExtra()])
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $requestEntityConfig,
                array_merge($entityMetadataExtras, [
                    new HateoasMetadataExtra($this->context->getFilterValues()),
                    new ActionMetadataExtra($action)
                ])
            )
            ->willReturn($requestEntityMetadata);

        self::assertSame($requestEntityMetadata, $this->context->getRequestMetadata()); // load metadata

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());

        // test that metadata are loaded only once
        self::assertSame($requestEntityMetadata, $this->context->getRequestMetadata());
    }

    public function testLoadRequestMetadataWhenNoRequestClassName(): void
    {
        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $this->context->setParentConfig($parentEntityConfig);

        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->context->getRequestMetadata());

        // test that metadata are loaded only once
        self::assertNull($this->context->getRequestMetadata());
    }

    public function testLoadRequestMetadataWhenExceptionOccurs(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $action = 'update_subresource';
        $requestEntityClass = 'Test\RequestClass';
        $exception = new \RuntimeException('some error');

        $parentEntityConfig = new EntityDefinitionConfig();
        $parentEntityConfig->setExcludeAll();
        $parentEntityConfig->set(ConfigUtil::REQUEST_TARGET_CLASS, $requestEntityClass);

        $requestEntityConfig = new EntityDefinitionConfig();
        $entityConfigExtras = [new EntityDefinitionConfigExtra($action)];
        $entityMetadata = new EntityMetadata('Test\Class');
        $entityMetadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setAction($action);
        $this->context->setParentClassName('Test\ParentClass');
        $this->context->setAssociationName('test');
        $this->context->setClassName('Test\Class');
        $this->context->setConfigExtras($entityConfigExtras);
        $this->context->setMetadata($entityMetadata);
        $this->context->setMetadataExtras($entityMetadataExtras);
        $this->context->setParentConfig($parentEntityConfig);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $entityConfigExtras
            )
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $requestEntityConfig]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $requestEntityClass,
                $version,
                new RequestType([$requestType]),
                $requestEntityConfig,
                array_merge($entityMetadataExtras, [new ActionMetadataExtra($action)])
            )
            ->willThrowException($exception);

        try {
            $this->context->getRequestMetadata(); // load metadata
        } catch (\RuntimeException $e) {
            self::assertSame($exception, $e);
        }

        self::assertSame($requestEntityConfig, $this->context->getRequestConfig());

        // test that metadata are loaded only once
        self::assertNull($this->context->getRequestMetadata());
    }

    public function testRequestMetadataWhenItIsSetExplicitly(): void
    {
        $requestEntityMetadata = new EntityMetadata('Test\RequestClass');
        $entityMetadata = new EntityMetadata('Test\Class');

        $this->context->setMetadata($entityMetadata);

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->context->setRequestMetadata($requestEntityMetadata);
        self::assertSame($requestEntityMetadata, $this->context->getRequestMetadata());

        // test remove metadata
        $this->context->setRequestMetadata(null);
        self::assertSame($entityMetadata, $this->context->getRequestMetadata());

        // test no entity metadata
        $this->context->setMetadata(null);
        self::assertNull($this->context->getRequestMetadata());
    }
}
