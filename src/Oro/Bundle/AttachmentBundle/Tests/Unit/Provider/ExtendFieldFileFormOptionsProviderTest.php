<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\ExtendFieldFileFormOptionsProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\UserBundle\Entity\User;

class ExtendFieldFileFormOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    private EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject $entityConfigManager;

    private ExtendFieldFileFormOptionsProvider $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new ExtendFieldFileFormOptionsProvider($this->entityConfigManager);
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
            [
                'fieldType' => 'file',
                'attachmentConfigValues' => [],
                'expectedOptions' => ['isExternalFile' => false],
            ],
            [
                'fieldType' => 'file',
                'attachmentConfigValues' => ['is_stored_externally' => false],
                'expectedOptions' => ['isExternalFile' => false],
            ],
            [
                'fieldType' => 'image',
                'attachmentConfigValues' => [],
                'expectedOptions' => ['isExternalFile' => false],
            ],
            [
                'fieldType' => 'image',
                'attachmentConfigValues' => ['is_stored_externally' => true],
                'expectedOptions' => ['isExternalFile' => true],
            ],
            [
                'fieldType' => 'multiFile',
                'attachmentConfigValues' => [],
                'expectedOptions' => [
                    'entry_options' => [
                        'file_options' => ['isExternalFile' => false],
                    ],
                ],
            ],
            [
                'fieldType' => 'multiFile',
                'attachmentConfigValues' => ['is_stored_externally' => true],
                'expectedOptions' => [
                    'entry_options' => [
                        'file_options' => ['isExternalFile' => true],
                    ],
                ],
            ],
            [
                'fieldType' => 'multiImage',
                'attachmentConfigValues' => [],
                'expectedOptions' => ['entry_options' => ['file_options' => ['isExternalFile' => false]]],
            ],
            [
                'fieldType' => 'multiImage',
                'attachmentConfigValues' => ['is_stored_externally' => true],
                'expectedOptions' => [
                    'entry_options' => [
                        'file_options' => ['isExternalFile' => true],
                    ],
                ],
            ],
        ];
    }
}
