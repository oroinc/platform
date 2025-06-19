<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\Config;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\MetaPropertiesConfigExtra;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FormContextTest extends TestCase
{
    private ConfigProvider&MockObject $configProvider;
    private MetadataProvider&MockObject $metadataProvider;
    private FormContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new FormContextStub($this->configProvider, $this->metadataProvider);
    }

    private function getConfig(array $data = []): Config
    {
        $result = new Config();
        foreach ($data as $sectionName => $config) {
            $result->set($sectionName, $config);
        }

        return $result;
    }

    public function testRequestId(): void
    {
        self::assertNull($this->context->getRequestId());

        $requestId = 'test';
        $this->context->setRequestId($requestId);
        self::assertSame($requestId, $this->context->getRequestId());
    }

    public function testRequestData(): void
    {
        self::assertSame([], $this->context->getRequestData());

        $requestData = ['key' => 'val'];
        $this->context->setRequestData($requestData);
        self::assertSame($requestData, $this->context->getRequestData());
    }

    public function testExisting(): void
    {
        self::assertFalse($this->context->isExisting());

        $this->context->setExisting(true);
        self::assertTrue($this->context->isExisting());
    }

    public function testIncludedData(): void
    {
        $includedData = [];
        $this->context->setIncludedData($includedData);
        self::assertSame($includedData, $this->context->getIncludedData());
    }

    public function testIncludedEntities(): void
    {
        self::assertNull($this->context->getIncludedEntities());

        $includedEntities = $this->createMock(IncludedEntityCollection::class);
        $this->context->setIncludedEntities($includedEntities);
        self::assertSame($includedEntities, $this->context->getIncludedEntities());

        $this->context->setIncludedEntities(null);
        self::assertNull($this->context->getIncludedEntities());
    }

    public function testAdditionalEntities(): void
    {
        self::assertSame([], $this->context->getAdditionalEntities());

        $entity1 = new \stdClass();
        $entity2 = new \stdClass();
        $entity3 = new \stdClass();

        $this->context->addAdditionalEntity($entity1);
        $this->context->addAdditionalEntity($entity2);
        self::assertSame([$entity1, $entity2], $this->context->getAdditionalEntities());

        $this->context->addAdditionalEntityToRemove($entity3);
        self::assertSame([$entity1, $entity2, $entity3], $this->context->getAdditionalEntities());

        self::assertFalse($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity1));
        self::assertFalse($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity2));
        self::assertTrue($this->context->getAdditionalEntityCollection()->shouldEntityBeRemoved($entity3));

        $this->context->addAdditionalEntity($entity1);
        $this->context->addAdditionalEntityToRemove($entity3);
        self::assertSame([$entity1, $entity2, $entity3], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity1);
        self::assertSame([$entity2, $entity3], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity3);
        self::assertSame([$entity2], $this->context->getAdditionalEntities());

        $this->context->removeAdditionalEntity($entity2);
        self::assertSame([], $this->context->getAdditionalEntities());
    }

    public function testEntityMapper(): void
    {
        $entityMapper = $this->createMock(EntityMapper::class);

        self::assertNull($this->context->getEntityMapper());

        $this->context->setEntityMapper($entityMapper);
        self::assertSame($entityMapper, $this->context->getEntityMapper());

        $this->context->setEntityMapper(null);
        self::assertNull($this->context->getEntityMapper());
    }

    public function testFormBuilder(): void
    {
        $formBuilder = $this->createMock(FormBuilderInterface::class);

        self::assertFalse($this->context->hasFormBuilder());
        self::assertNull($this->context->getFormBuilder());

        $this->context->setFormBuilder($formBuilder);
        self::assertTrue($this->context->hasFormBuilder());
        self::assertSame($formBuilder, $this->context->getFormBuilder());

        $this->context->setFormBuilder(null);
        self::assertFalse($this->context->hasFormBuilder());
        self::assertNull($this->context->getFormBuilder());
    }

    public function testForm(): void
    {
        $form = $this->createMock(FormInterface::class);

        self::assertFalse($this->context->hasForm());
        self::assertNull($this->context->getForm());

        $this->context->setForm($form);
        self::assertTrue($this->context->hasForm());
        self::assertSame($form, $this->context->getForm());

        $this->context->setForm(null);
        self::assertFalse($this->context->hasForm());
        self::assertNull($this->context->getForm());
    }

    public function testSkipFormValidation(): void
    {
        self::assertFalse($this->context->isFormValidationSkipped());

        $this->context->skipFormValidation(true);
        self::assertTrue($this->context->isFormValidationSkipped());

        $this->context->skipFormValidation(false);
        self::assertFalse($this->context->isFormValidationSkipped());
    }

    public function testFormOptions(): void
    {
        self::assertNull($this->context->getFormOptions());

        $this->context->setFormOptions(['option' => 'value']);
        self::assertEquals(['option' => 'value'], $this->context->getFormOptions());

        $this->context->setFormOptions([]);
        self::assertNull($this->context->getFormOptions());

        $this->context->setFormOptions(null);
        self::assertNull($this->context->getFormOptions());
    }

    public function testSetConfigExtras(): void
    {
        $normalizedExpandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association1']);
        $normalizedFilterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity1' => ['field1']]);
        $normalizedMetaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $normalizedMetaPropertiesConfigExtra->addMetaProperty('property1', 'string');
        $normalizedAnotherConfigExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([
            $normalizedExpandRelatedEntitiesConfigExtra,
            $normalizedFilterFieldsConfigExtra,
            $normalizedMetaPropertiesConfigExtra,
            $normalizedAnotherConfigExtra
        ]);
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());

        $expandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association2']);
        $filterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity2' => ['field2']]);
        $metaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $metaPropertiesConfigExtra->addMetaProperty('property2', 'string');
        $anotherConfigExtra = new TestConfigExtra('extra2');
        $this->context->setConfigExtras([
            $expandRelatedEntitiesConfigExtra,
            $filterFieldsConfigExtra,
            $metaPropertiesConfigExtra,
            $anotherConfigExtra
        ]);
        self::assertSame(
            [
                $expandRelatedEntitiesConfigExtra,
                $filterFieldsConfigExtra,
                $metaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertEquals(
            [
                new ExpandRelatedEntitiesConfigExtra([]),
                new FilterFieldsConfigExtra(['entity2' => null]),
                $anotherConfigExtra
            ],
            $this->context->getConfigExtras()
        );
    }

    public function testAddAndRemoveConfigExtra(): void
    {
        $normalizedExpandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association1']);
        $normalizedFilterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity1' => ['field1']]);
        $normalizedMetaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $normalizedMetaPropertiesConfigExtra->addMetaProperty('property1', 'string');
        $normalizedAnotherConfigExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([
            $normalizedExpandRelatedEntitiesConfigExtra,
            $normalizedFilterFieldsConfigExtra,
            $normalizedMetaPropertiesConfigExtra,
            $normalizedAnotherConfigExtra
        ]);
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());

        $expandRelatedEntitiesConfigExtra = new ExpandRelatedEntitiesConfigExtra(['association2']);
        $filterFieldsConfigExtra = new FilterFieldsConfigExtra(['entity2' => ['field2']]);
        $metaPropertiesConfigExtra = new MetaPropertiesConfigExtra();
        $metaPropertiesConfigExtra->addMetaProperty('property2', 'string');
        $anotherConfigExtra = new TestConfigExtra('extra2');
        $this->context->addConfigExtra($expandRelatedEntitiesConfigExtra);
        $this->context->addConfigExtra($filterFieldsConfigExtra);
        $this->context->addConfigExtra($metaPropertiesConfigExtra);
        $this->context->addConfigExtra($anotherConfigExtra);
        self::assertSame(
            [
                $expandRelatedEntitiesConfigExtra,
                $filterFieldsConfigExtra,
                $metaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertEquals(
            [
                new ExpandRelatedEntitiesConfigExtra([]),
                new FilterFieldsConfigExtra(['entity2' => null]),
                $anotherConfigExtra
            ],
            $this->context->getConfigExtras()
        );

        $this->context->removeConfigExtra($expandRelatedEntitiesConfigExtra->getName());
        $this->context->removeConfigExtra($filterFieldsConfigExtra->getName());
        $this->context->removeConfigExtra($metaPropertiesConfigExtra->getName());
        $this->context->removeConfigExtra($anotherConfigExtra->getName());
        self::assertSame(
            [
                $normalizedExpandRelatedEntitiesConfigExtra,
                $normalizedFilterFieldsConfigExtra,
                $normalizedMetaPropertiesConfigExtra,
                $normalizedAnotherConfigExtra
            ],
            $this->context->getNormalizedEntityConfigExtras()
        );
        self::assertSame([], $this->context->getConfigExtras());
    }

    public function testNormalizedEntityConfigExtras(): void
    {
        self::assertSame([], $this->context->getNormalizedEntityConfigExtras());

        $configExtra = new TestConfigExtra('extra1');
        $this->context->setNormalizedEntityConfigExtras([$configExtra]);
        self::assertSame([$configExtra], $this->context->getNormalizedEntityConfigExtras());
    }

    public function testGetNormalizedConfigWhenNoNormalizedConfigExtras(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1')
        ];

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));

        self::assertEquals($config, $this->context->getNormalizedConfig()); // load config

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getNormalizedConfig());
    }

    public function testGetNormalizedConfigWhenNormalizedConfigExtrasExist(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ];

        $config = new EntityDefinitionConfig();
        $config->setExcludeAll();

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));

        self::assertEquals($config, $this->context->getNormalizedConfig()); // load config

        // test that a config is loaded only once
        self::assertEquals($config, $this->context->getNormalizedConfig());
    }

    public function testGetNormalizedConfigWhenNormalizedConfigExtrasExistAndNoNormalizedConfig(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => null]));

        self::assertNull($this->context->getNormalizedConfig()); // load config

        // test that a config is loaded only once
        self::assertNull($this->context->getNormalizedConfig());
    }

    public function testGetNormalizedConfigWhenNormalizedConfigExtrasExistAndNoEntityClass(): void
    {
        $this->context->setVersion('1.1');
        $this->context->getRequestType()->add('rest');
        $this->context->setConfigExtras([
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ]);

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        self::assertNull($this->context->getNormalizedConfig()); // load config

        // test that a config is loaded only once
        self::assertNull($this->context->getNormalizedConfig());
    }

    public function testGetNormalizedConfigWhenItIsSetExplicitly(): void
    {
        $config = new EntityDefinitionConfig();

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');

        $this->context->setNormalizedConfig($config);

        self::assertSame($config, $this->context->getNormalizedConfig());

        // test remove config
        $this->context->setNormalizedConfig(null);
        self::assertNull($this->context->getNormalizedConfig());
    }

    public function testGetNormalizedMetadataWhenNoNormalizedConfigExtras(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1')
        ];

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn($metadata);

        self::assertSame($metadata, $this->context->getNormalizedMetadata()); // load metadata

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getNormalizedMetadata());
    }

    public function testGetNormalizedMetadataWhenNormalizedConfigExtrasExist(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ];

        $config = new EntityDefinitionConfig();
        $metadata = new EntityMetadata('Test\Entity');
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn($metadata);

        self::assertSame($metadata, $this->context->getNormalizedMetadata()); // load metadata

        // test that metadata are loaded only once
        self::assertSame($metadata, $this->context->getNormalizedMetadata());
    }

    public function testGetNormalizedMetadataWhenNormalizedConfigExtrasExistAndNoNormalizedMetadata(): void
    {
        $version = '1.1';
        $requestType = 'rest';
        $entityClass = 'Test\Class';
        $configExtras = [
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ];

        $config = new EntityDefinitionConfig();
        $metadataExtras = [new TestMetadataExtra('extra1')];

        $this->context->setVersion($version);
        $this->context->getRequestType()->add($requestType);
        $this->context->setConfigExtras($configExtras);
        $this->context->setMetadataExtras($metadataExtras);
        $this->context->setClassName($entityClass);

        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entityClass, $version, new RequestType([$requestType]), $configExtras)
            ->willReturn($this->getConfig([ConfigUtil::DEFINITION => $config]));
        $this->metadataProvider->expects(self::once())
            ->method('getMetadata')
            ->with(
                $entityClass,
                $version,
                new RequestType([$requestType]),
                $config,
                $metadataExtras
            )
            ->willReturn(null);

        self::assertNull($this->context->getNormalizedMetadata()); // load metadata

        // test that metadata are loaded only once
        self::assertNull($this->context->getNormalizedMetadata());
    }

    public function testGetNormalizedMetadataWhenNormalizedConfigExtrasExistAndNoEntityClass(): void
    {
        $this->context->setVersion('1.1');
        $this->context->getRequestType()->add('rest');
        $this->context->setConfigExtras([
            new TestConfigExtra('extra1'),
            new ExpandRelatedEntitiesConfigExtra(['association'])
        ]);

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->context->getNormalizedMetadata()); // load metadata

        // test that metadata are loaded only once
        self::assertNull($this->context->getNormalizedMetadata());
    }

    public function testGetNormalizedMetadataWhenItIsSetExplicitly(): void
    {
        $metadata = new EntityMetadata('Test\Entity');

        $this->context->setClassName('Test\Class');

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->metadataProvider->expects(self::never())
            ->method('getMetadata');

        $this->context->setNormalizedMetadata($metadata);

        self::assertSame($metadata, $this->context->getNormalizedMetadata());

        // test remove metadata
        $this->context->setNormalizedMetadata(null);
        self::assertNull($this->context->getNormalizedMetadata());
    }

    public function testGetAllEntities(): void
    {
        self::assertSame([], $this->context->getAllEntities());
        self::assertSame([], $this->context->getAllEntities(true));

        $entity = new \stdClass();
        $this->context->setResult($entity);
        self::assertSame([$entity], $this->context->getAllEntities());
        self::assertSame([$entity], $this->context->getAllEntities(true));

        $includedEntity = new \stdClass();
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->context->getIncludedEntities()
            ->add($includedEntity, \stdClass::class, 1, $this->createMock(IncludedEntityData::class));
        self::assertSame([$entity, $includedEntity], $this->context->getAllEntities());
        self::assertSame([$entity], $this->context->getAllEntities(true));
    }
}
