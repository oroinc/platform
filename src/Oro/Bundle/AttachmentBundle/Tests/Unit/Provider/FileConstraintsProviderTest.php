<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\DependencyInjection\Configuration;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager as SystemConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileConstraintsProviderTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var SystemConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $systemConfigManager;

    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var FileConstraintsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->systemConfigManager = $this->createMock(SystemConfigManager::class);
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);

        $this->provider = new FileConstraintsProvider($this->systemConfigManager, $this->entityConfigManager);

        $this->setUpLoggerMock($this->provider);
    }

    public function testGetFileMimeTypes(): void
    {
        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types', '', false, null)
            ->willReturn('sample/type1,sample/type2');

        $this->assertEquals(['sample/type1', 'sample/type2'], $this->provider->getFileMimeTypes());
    }

    public function testGetImageMimeTypes(): void
    {
        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types', '', false, null)
            ->willReturn('sample/type1,sample/type2');

        $this->assertEquals(['sample/type1', 'sample/type2'], $this->provider->getImageMimeTypes());
    }


    /**
     * @dataProvider mimeTypesDataProvider
     *
     * @param string|null $fileMimeTypes
     * @param string|null $imageMimeTypes
     * @param array $expected
     */
    public function testGetMimeTypes(?string $fileMimeTypes, ?string $imageMimeTypes, array $expected): void
    {
        $this->systemConfigManager
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_attachment.upload_file_mime_types', '', false, null, $fileMimeTypes],
                    ['oro_attachment.upload_image_mime_types', '', false, null, $imageMimeTypes],
                ]
            );

        $this->assertEquals($expected, $this->provider->getMimeTypes());
    }

    /**
     * @return array
     */
    public function mimeTypesDataProvider(): array
    {
        return [
            [null, null, []],
            [
                'sample/type1',
                'sample/type2',
                ['sample/type1', 'sample/type2'],
            ],
            [
                'sample/type1',
                '',
                ['sample/type1'],
            ],
            [
                '',
                'sample/type2',
                ['sample/type2'],
            ],
            [
                'sample/type1,sample/type2',
                'sample/type2',
                ['sample/type1', 'sample/type2'],
            ],
        ];
    }

    /**
     * @dataProvider mimeTypesAsChoicesDataProvider
     *
     * @param string|null $fileMimeTypes
     * @param string|null $imageMimeTypes
     * @param array $expected
     */
    public function testGetMimeTypesAsChoices(?string $fileMimeTypes, ?string $imageMimeTypes, array $expected): void
    {
        $this->systemConfigManager
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_attachment.upload_file_mime_types', '', false, null, $fileMimeTypes],
                    ['oro_attachment.upload_image_mime_types', '', false, null, $imageMimeTypes],
                ]
            );

        $this->assertEquals($expected, $this->provider->getMimeTypesAsChoices());
    }

    /**
     * @return array
     */
    public function mimeTypesAsChoicesDataProvider(): array
    {
        return [
            [null, null, []],
            [
                'sample/type1',
                'sample/type2',
                ['sample/type1' => 'sample/type1', 'sample/type2' => 'sample/type2'],
            ],
            [
                'sample/type1',
                '',
                ['sample/type1' => 'sample/type1'],
            ],
            [
                '',
                'sample/type2',
                ['sample/type2' => 'sample/type2'],
            ],
            [
                'sample/type1,sample/type2',
                'sample/type2',
                ['sample/type1' => 'sample/type1', 'sample/type2' => 'sample/type2'],
            ],
        ];
    }

    public function testGetAllowedMimeTypesForEntityWhenNoFieldConfig(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willThrowException(new RuntimeException());

        $this->assertLoggerWarningMethodCalled();

        $this->systemConfigManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_attachment.upload_file_mime_types', '', false, null, 'sample/type1'],
                    ['oro_attachment.upload_image_mime_types', '', false, null, 'sample/type2'],
                ]
            );

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntity($entityClass)
        );
    }

    public function testGetAllowedMimeTypesForEntityWhenNoMimeTypes(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willReturn($entityConfig = $this->createMock(ConfigInterface::class));

        $entityConfig
            ->expects($this->once())
            ->method('get')
            ->with('mimetypes')
            ->willReturn('');

        $this->systemConfigManager
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_attachment.upload_file_mime_types', '', false, null, 'sample/type1'],
                    ['oro_attachment.upload_image_mime_types', '', false, null, 'sample/type2'],
                ]
            );

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntity($entityClass)
        );
    }

    public function testGetAllowedMimeTypesForEntity(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willReturn($entityConfig = $this->createMock(ConfigInterface::class));

        $entityConfig
            ->expects($this->once())
            ->method('get')
            ->with('mimetypes')
            ->willReturn('sample/type1,sample/type2');

        $this->systemConfigManager
            ->expects($this->never())
            ->method('get');

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntity($entityClass)
        );
    }

    public function testGetAllowedMimeTypesForEntityFieldWhenNoFieldConfig(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willThrowException(new RuntimeException());

        $this->assertLoggerWarningMethodCalled();

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types', '', false, null)
            ->willReturn('sample/type1,sample/type2');

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetAllowedMimeTypesForEntityFieldWhenImageAndNoMimeTypes(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects($this->once())
            ->method('get')
            ->with('mimetypes')
            ->willReturn('');

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn('image');

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_image_mime_types', '', false, null)
            ->willReturn('sample/type1,sample/type2');

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetAllowedMimeTypesForEntityFieldWhenNotImageAndNoMimeTypes(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects($this->once())
            ->method('get')
            ->with('mimetypes')
            ->willReturn('');

        $entityFieldConfig
            ->expects($this->once())
            ->method('getId')
            ->willReturn($fieldConfigId = $this->createMock(FieldConfigId::class));

        $fieldConfigId
            ->expects($this->once())
            ->method('getFieldType')
            ->willReturn('another_type');

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.upload_file_mime_types', '', false, null)
            ->willReturn('sample/type1,sample/type2');

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetAllowedMimeTypesForEntityField(): void
    {
        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects($this->once())
            ->method('get')
            ->with('mimetypes')
            ->willReturn('sample/type1,sample/type2');

        $entityFieldConfig
            ->expects($this->never())
            ->method('getId');

        $this->assertEquals(
            ['sample/type1', 'sample/type2'],
            $this->provider->getAllowedMimeTypesForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetMaxSize(): void
    {
        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.maxsize', '', false, null)
            ->willReturn(10);

        $this->assertEquals(10 * Configuration::BYTES_MULTIPLIER, $this->provider->getMaxSize());
    }

    public function testGetMaxSizeForEntityWhenNoEntityConfig(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willThrowException(new RuntimeException());

        $this->assertLoggerWarningMethodCalled();

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.maxsize', '', false, null)
            ->willReturn(10);

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntity($entityClass)
        );
    }

    public function testGetMaxSizeForEntityWhenNoMaxSize(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willReturn($entityConfig = $this->createMock(ConfigInterface::class));

        $entityConfig
            ->expects($this->once())
            ->method('get')
            ->with('maxsize')
            ->willReturn(null);

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.maxsize', '', false, null)
            ->willReturn(10);

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntity($entityClass)
        );
    }

    public function testGetMaxSizeForEntity(): void
    {
        $this->entityConfigManager
            ->method('getEntityConfig')
            ->with('attachment', $entityClass = \stdClass::class)
            ->willReturn($entityConfig = $this->createMock(ConfigInterface::class));

        $entityConfig
            ->expects($this->once())
            ->method('get')
            ->with('maxsize')
            ->willReturn(10);

        $this->systemConfigManager
            ->expects($this->never())
            ->method('get');

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntity($entityClass)
        );
    }

    public function testGetMaxSizeForEntityFieldWhenNoFieldConfig(): void
    {
        $this->entityConfigManager
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willThrowException(new RuntimeException());

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.maxsize', '', false, null)
            ->willReturn(10);

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetMaxSizeForEntityFieldWhenNoMaxSize(): void
    {
        $this->entityConfigManager
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects($this->once())
            ->method('get')
            ->with('maxsize')
            ->willReturn(null);

        $this->systemConfigManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_attachment.maxsize', '', false, null)
            ->willReturn(10);

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntityField($entityClass, $fieldName)
        );
    }

    public function testGetMaxSizeForEntityField(): void
    {
        $this->entityConfigManager
            ->method('getFieldConfig')
            ->with('attachment', $entityClass = \stdClass::class, $fieldName = 'sampleField')
            ->willReturn($entityFieldConfig = $this->createMock(ConfigInterface::class));

        $entityFieldConfig
            ->expects($this->once())
            ->method('get')
            ->with('maxsize')
            ->willReturn(10);

        $this->systemConfigManager
            ->expects($this->never())
            ->method('get');

        $this->assertEquals(
            10 * Configuration::BYTES_MULTIPLIER,
            $this->provider->getMaxSizeForEntityField($entityClass, $fieldName)
        );
    }
}
