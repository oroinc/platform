<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Provider;

use Oro\Bundle\DigitalAssetBundle\Provider\ExtendFieldFileDamFormOptionsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\UserBundle\Entity\User;

class ExtendFieldFileDamFormOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    private EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject $entityConfigManager;

    private ExtendFieldFileDamFormOptionsProvider $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new ExtendFieldFileDamFormOptionsProvider($this->entityConfigManager);
    }

    public function testGetOptionsDoesNothingWhenNotSupportedType(): void
    {
        $className = User::class;
        $fieldName = 'avatar';
        $fieldType = 'string';
        $formFieldConfig = new Config(new FieldConfigId('form', $className, $fieldName, $fieldType));
        $this->entityConfigManager
            ->expects(self::once())
            ->method('getFieldConfig')
            ->with('form', $className, $fieldName)
            ->willReturn($formFieldConfig);

        self::assertEquals([], $this->provider->getOptions($className, $fieldName));
    }

    /**
     * @dataProvider getOptionsAddsIsExternalFileWhenSupportedTypeDataProvider
     */
    public function testGetOptionsAddsIsExternalFileWhenSupportedType(
        string $fieldType,
        array $attachmentConfigValues,
        array $expectedOptions
    ): void {
        $className = User::class;
        $fieldName = 'avatar';
        $formFieldConfig = new Config(new FieldConfigId('form', $className, $fieldName, $fieldType));
        $attachmentFieldConfig = new Config(
            new FieldConfigId('form', $className, $fieldName, $fieldType),
            $attachmentConfigValues
        );
        $this->entityConfigManager
            ->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->withConsecutive(['form', $className, $fieldName], ['attachment', $className, $fieldName])
            ->willReturnOnConsecutiveCalls($formFieldConfig, $attachmentFieldConfig);

        self::assertEquals($expectedOptions, $this->provider->getOptions($className, $fieldName));
    }

    public function getOptionsAddsIsExternalFileWhenSupportedTypeDataProvider(): array
    {
        return [
            'dam_widget_enabled is false when use_dam is not true' => [
                'fieldType' => 'file',
                'attachmentConfigValues' => [],
                'expectedOptions' => ['dam_widget_enabled' => false],
            ],
            'dam_widget_enabled is false when is_stored_externally is true and use_dam is not true' => [
                'fieldType' => 'image',
                'attachmentConfigValues' => ['is_stored_externally' => true],
                'expectedOptions' => ['dam_widget_enabled' => false],
            ],
            'dam_widget_enabled is false when is_stored_externally is false and use_dam is not true' => [
                'fieldType' => 'multiFile',
                'attachmentConfigValues' => ['is_stored_externally' => false],
                'expectedOptions' => ['entry_options' => ['file_options' => ['dam_widget_enabled' => false]]],
            ],
            'dam_widget_enabled is true when is_stored_externally is false and use_dam is true' => [
                'fieldType' => 'multiImage',
                'attachmentConfigValues' => ['is_stored_externally' => false, 'use_dam' => true],
                'expectedOptions' => ['entry_options' => ['file_options' => ['dam_widget_enabled' => true]]],
            ],
        ];
    }
}
