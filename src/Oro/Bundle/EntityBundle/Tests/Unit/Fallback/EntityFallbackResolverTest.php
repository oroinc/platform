<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Fallback;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackFieldConfigurationMissingException;
use Oro\Bundle\EntityBundle\Exception\Fallback\FallbackProviderNotFoundException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackKeyException;
use Oro\Bundle\EntityBundle\Exception\Fallback\InvalidFallbackTypeException;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Fallback\Provider\EntityFallbackProviderInterface;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\Fallback\Stub\FallbackContainingEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\Testing\Unit\TestContainerBuilder;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFallbackResolverTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_FALLBACK = 'testFallback';

    /** @var ConfigBag|\PHPUnit\Framework\MockObject\MockObject */
    private $configBag;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var SystemConfigurationFormProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $formProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configInterface;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formProvider = $this->createMock(SystemConfigurationFormProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configInterface = $this->createMock(ConfigInterface::class);
    }

    /**
     * @param EntityFallbackProviderInterface[] $fallbackProviders [id => provider, ...]
     */
    private function getEntityFallbackResolver(array $fallbackProviders): EntityFallbackResolver
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($fallbackProviders as $id => $provider) {
            $containerBuilder->add($id, $provider);
        }

        return new EntityFallbackResolver(
            $containerBuilder->getContainer($this),
            $this->entityConfigProvider,
            $this->formProvider,
            $this->configManager,
            $this->configBag,
            $this->doctrineHelper
        );
    }

    public function testGetTypeReturnsDataType()
    {
        $this->setDefaultConfigInterfaceMock();
        $this->configInterface->expects($this->once())
            ->method('getValues')
            ->willReturn($this->getEntityConfiguration());

        $formDescription = ['data_type' => 'testDataType'];
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->willReturn($formDescription);

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEquals($formDescription['data_type'], $resolver->getType(new \stdClass(), 'testProperty'));
    }

    public function testGetTypeFromEntityFieldConfig()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->exactly(2))
            ->method('getValues')
            ->willReturn($entityConfig);
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->willReturn([]);

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEquals(
            $entityConfig[EntityFieldFallbackValue::FALLBACK_TYPE],
            $resolver->getType(new \stdClass(), 'testProperty')
        );
    }

    public function testGetTypeThrowsExceptionOnInvalidType()
    {
        $this->expectException(InvalidFallbackTypeException::class);
        $this->expectExceptionMessage("Invalid fallback data type 'invalidType' provided.");

        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $entityConfig[EntityFieldFallbackValue::FALLBACK_TYPE] = 'invalidType';
        $this->configInterface->expects($this->exactly(2))
            ->method('getValues')
            ->willReturn($entityConfig);
        $this->configBag->expects($this->once())
            ->method('getFieldsRoot')
            ->willReturn([]);

        $resolver = $this->getEntityFallbackResolver([]);
        $resolver->getType(new \stdClass(), 'testProperty');
    }

    public function testGetSystemConfigFormReturnsEmptyArrayIfNoConfigName()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $entityConfig[EntityFieldFallbackValue::FALLBACK_LIST] = [];
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEmpty($resolver->getSystemConfigFormDescription(new \stdClass(), 'testProperty'));
    }

    public function testGetSystemConfigFormReturnsEmptyArrayIfNoFormDescription()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);
        $this->configBag->expects($this->any())
            ->method('getFieldsRoot')
            ->willReturn([]);

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEmpty($resolver->getSystemConfigFormDescription(new \stdClass(), 'testProperty'));
    }

    public function testGetSystemConfigFormReturnsCorrectArray()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);
        $formDescr = ['data_type' => 'testType'];
        $this->configBag->expects($this->any())
            ->method('getFieldsRoot')
            ->willReturn($formDescr);

        $resolver = $this->getEntityFallbackResolver([]);
        $result = $resolver->getSystemConfigFormDescription(new \stdClass(), 'testProperty');
        $this->assertSame($formDescr, $result);
    }

    public function testIsFallbackSupported()
    {
        $provider = $this->createMock(SystemConfigFallbackProvider::class);
        $provider->expects($this->once())
            ->method('isFallbackSupported')
            ->willReturn(true);

        $resolver = $this->getEntityFallbackResolver(['systemConfig' => $provider]);
        $this->assertTrue($resolver->isFallbackSupported(new \stdClass(), 'testProperty', 'systemConfig'));
    }

    public function testIsFallbackConfigured()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertTrue($resolver->isFallbackConfigured(
            SystemConfigFallbackProvider::FALLBACK_ID,
            new \stdClass(),
            'testProperty'
        ));
        $this->assertFalse($resolver->isFallbackConfigured(
            'some_wrong_fallback_id',
            new \stdClass(),
            'testProperty'
        ));
    }

    public function testGetFallbackConfigReturnsFullConfig()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $resolver = $this->getEntityFallbackResolver([]);
        $result = $resolver->getFallbackConfig(new \stdClass(), 'testProperty');
        $this->assertSame($entityConfig, $result);
    }

    public function testGetFallbackConfigReturnsConfigByConfigName()
    {
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $resolver = $this->getEntityFallbackResolver([]);
        $result = $resolver->getFallbackConfig(
            new \stdClass(),
            'testProperty',
            EntityFieldFallbackValue::FALLBACK_LIST
        );
        $this->assertSame($entityConfig[EntityFieldFallbackValue::FALLBACK_LIST], $result);
    }

    public function testGetFallbackConfigThrowsExceptionIfNoConfigWithName()
    {
        $this->expectException(FallbackFieldConfigurationMissingException::class);
        $this->expectExceptionMessage(
            "You must define the fallback configuration 'nonExistentConfig' for class 'stdClass', field 'testProperty'"
        );

        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $resolver = $this->getEntityFallbackResolver([]);
        $resolver->getFallbackConfig(new \stdClass(), 'testProperty', 'nonExistentConfig');
    }

    public function testGetFallbackProviderThrowsException()
    {
        $this->expectException(FallbackProviderNotFoundException::class);
        $this->expectExceptionMessage(
            'Fallback provider for fallback with identification key "nonExistent" not found.'
        );

        $resolver = $this->getEntityFallbackResolver([]);
        $resolver->getFallbackProvider('nonExistent');
    }

    public function testGetFallbackProviderReturnsProvider()
    {
        $provider = $this->createMock(SystemConfigFallbackProvider::class);

        $resolver = $this->getEntityFallbackResolver(['systemConfig' => $provider]);
        $this->assertSame($provider, $resolver->getFallbackProvider('systemConfig'));
    }

    public function testGetFallbackValueReturnsNonFallbackValue()
    {
        $entity = new FallbackContainingEntity('test');
        $this->setDefaultConfigInterfaceMock();
        $entityConfig = $this->getEntityConfiguration();
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $this->setUpTypeResolution('string');

        $resolver = $this->getEntityFallbackResolver([
            SystemConfigFallbackProvider::FALLBACK_ID => $this->createMock(EntityFallbackProviderInterface::class)
        ]);
        $this->assertEquals('test', $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackFromFallbackList()
    {
        // set entity with null field values
        $entity = new FallbackContainingEntity();
        // set fallback entity with a value
        $entity2 = new FallbackContainingEntity();
        $fallbackValue = new EntityFieldFallbackValue();
        $expectedValue = ['testVALUE'];
        $fallbackValue->setArrayValue($expectedValue);
        $entity2->testProperty2 = $fallbackValue;

        $entityConfig = $this->getEntityConfiguration();
        $entityConfig[EntityFieldFallbackValue::FALLBACK_LIST][self::TEST_FALLBACK] = [
            EntityFallbackResolver::FALLBACK_FIELD_NAME => 'testProperty2',
        ];
        $entityConfigMock = $this->createMock(ConfigInterface::class);
        $entityConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $entity2Config = $this->getEntityConfiguration();
        $entity2Config[EntityFieldFallbackValue::FALLBACK_LIST]['testFallback2'] = ['fieldName' => 'testProperty2'];

        $entity2ConfigMock = $this->createMock(ConfigInterface::class);
        $entity2ConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entity2Config);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [get_class($entity), 'testProperty', $entityConfigMock],
                [get_class($entity2), 'testProperty2', $entity2ConfigMock],
            ]);
        $this->configBag->expects($this->any())
            ->method('getFieldsRoot')
            ->willReturn(['data_type' => 'array']);

        // first fallback in the list
        $systemProvider = $this->createMock(EntityFallbackProviderInterface::class);

        // second fallback in the list
        $testProvider = $this->createMock(EntityFallbackProviderInterface::class);
        $testProvider->expects($this->once())
            ->method('getFallbackHolderEntity')
            ->willReturn($entity2);

        $resolver = $this->getEntityFallbackResolver([
            SystemConfigFallbackProvider::FALLBACK_ID => $systemProvider,
            self::TEST_FALLBACK => $testProvider
        ]);
        $this->assertEquals($expectedValue, $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueReturnsOwnValue()
    {
        $this->setDefaultConfigInterfaceMock();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setScalarValue('someValue');
        $entity = new FallbackContainingEntity($fallbackValue);

        $this->setUpTypeResolution('string');

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEquals('someValue', $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueReturnsOwnBoolean()
    {
        $this->setDefaultConfigInterfaceMock();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setScalarValue('someValue');
        $entity = new FallbackContainingEntity($fallbackValue);

        $this->setUpTypeResolution('boolean');

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertTrue($resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueReturnsOwnInt()
    {
        $this->setDefaultConfigInterfaceMock();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setScalarValue('someValue');
        $entity = new FallbackContainingEntity($fallbackValue);

        $this->setUpTypeResolution('integer');

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEquals(0, $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueReturnsOwnArray()
    {
        $this->setDefaultConfigInterfaceMock();
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setArrayValue(['test']);
        $entity = new FallbackContainingEntity($fallbackValue);

        $this->setUpTypeResolution('array');

        $resolver = $this->getEntityFallbackResolver([]);
        $this->assertEquals(['test'], $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueThrowsInvalidKeyException()
    {
        $this->expectException(InvalidFallbackKeyException::class);
        $this->expectExceptionMessage('Invalid fallback key "nonExistentProvider" provided');

        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('nonExistentProvider');
        $entity = new FallbackContainingEntity($fallbackValue);
        $entityConfig = $this->getEntityConfiguration();
        $entityConfigMock = $this->createMock(ConfigInterface::class);
        $entityConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($entityConfigMock);

        $resolver = $this->getEntityFallbackResolver([]);
        $resolver->getFallbackValue($entity, 'testProperty');
    }

    public function testGetFallbackValueReturnsDirectValue()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback(self::TEST_FALLBACK);
        $entity = new FallbackContainingEntity($fallbackValue);
        $entityConfig = $this->getEntityConfiguration();
        $listKey = EntityFieldFallbackValue::FALLBACK_LIST;
        $entityConfig[$listKey][self::TEST_FALLBACK] = ['fieldName' => 'testProperty'];
        $entityConfig[EntityFieldFallbackValue::FALLBACK_TYPE] = 'string';
        $entityConfigMock = $this->createMock(ConfigInterface::class);
        $entityConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($entityConfigMock);
        $testProvider = $this->createMock(EntityFallbackProviderInterface::class);
        $testProvider->expects($this->once())
            ->method('getFallbackHolderEntity')
            ->willReturn('directValue');

        $resolver = $this->getEntityFallbackResolver([self::TEST_FALLBACK => $testProvider]);
        $this->assertEquals('directValue', $resolver->getFallbackValue($entity, 'testProperty'));
    }

    public function testGetFallbackValueReturnsFallbackValue()
    {
        $fallbackValue = new EntityFieldFallbackValue();
        $fallbackValue->setFallback('testFallback');
        $entity = new FallbackContainingEntity($fallbackValue);
        $entity2 = new FallbackContainingEntity(null, 'expectedValue');

        $entityConfig = $this->getEntityConfiguration();
        $listKey = EntityFieldFallbackValue::FALLBACK_LIST;
        $entityConfig[$listKey][self::TEST_FALLBACK] = ['fieldName' => 'testProperty2'];
        $entityConfigMock = $this->createMock(ConfigInterface::class);
        $entityConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entityConfig);

        $entity2Config = $this->getEntityConfiguration();
        $entity2Config[$listKey][self::TEST_FALLBACK] = ['fieldName' => 'testProperty2'];
        $entity2ConfigMock = $this->createMock(ConfigInterface::class);
        $entity2ConfigMock->expects($this->any())
            ->method('getValues')
            ->willReturn($entity2Config);
        $testProvider = $this->createMock(EntityFallbackProviderInterface::class);
        $testProvider->expects($this->once())
            ->method('getFallbackHolderEntity')
            ->willReturn($entity2);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
                [get_class($entity), 'testProperty', $entityConfigMock],
                [get_class($entity2), 'testProperty2', $entity2ConfigMock],
            ]);
        $this->setUpTypeResolution('string');

        $resolver = $this->getEntityFallbackResolver([self::TEST_FALLBACK => $testProvider]);
        $this->assertEquals('expectedValue', $resolver->getFallbackValue($entity, 'testProperty'));
    }

    /**
     * @dataProvider getRequiredFallbackFieldByTypeProvider
     */
    public function testGetRequiredFallbackFieldByType(
        string $type,
        ?string $expectedFieldName,
        bool $throwException = false
    ) {
        if ($throwException) {
            $this->expectException(InvalidFallbackTypeException::class);
        }

        $resolver = $this->getEntityFallbackResolver([]);
        $result = $resolver->getRequiredFallbackFieldByType($type);
        if (!$throwException) {
            $this->assertEquals($expectedFieldName, $result);
        }
    }

    public function getRequiredFallbackFieldByTypeProvider(): array
    {
        return [
            [EntityFallbackResolver::TYPE_BOOLEAN, EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD],
            [EntityFallbackResolver::TYPE_INTEGER, EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD],
            [EntityFallbackResolver::TYPE_DECIMAL, EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD],
            [EntityFallbackResolver::TYPE_STRING, EntityFieldFallbackValue::FALLBACK_SCALAR_FIELD],
            [EntityFallbackResolver::TYPE_ARRAY, EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD],
            ['invalidType', null, true],
        ];
    }

    private function setUpTypeResolution(string $type): void
    {
        $this->configInterface->expects($this->any())
            ->method('getValues')
            ->willReturn($this->getEntityConfiguration());
        $this->configBag->expects($this->any())
            ->method('getFieldsRoot')
            ->willReturn(['data_type' => $type]);
    }

    private function getEntityConfiguration(): array
    {
        return [
            EntityFieldFallbackValue::FALLBACK_LIST => [
                SystemConfigFallbackProvider::FALLBACK_ID => ['configName' => 'test_config_name'],
            ],
            EntityFieldFallbackValue::FALLBACK_TYPE => 'boolean',
        ];
    }

    private function setDefaultConfigInterfaceMock(): void
    {
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->configInterface);
    }
}
