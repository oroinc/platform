<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Model\ExternalFile;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class FileNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private AttachmentEntityConfigProviderInterface|\PHPUnit\Framework\MockObject\MockObject
        $attachmentEntityConfigProvider;

    private FileNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->attachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->normalizer = new FileNormalizer(
            $this->attachmentManager,
            $this->fileManager,
            $this->attachmentEntityConfigProvider
        );
    }

    /**
     * @dataProvider supportsDenormalizationData
     */
    public function testSupportsDenormalization($type, $isSupport): void
    {
        self::assertEquals($isSupport, $this->normalizer->supportsDenormalization([], $type));
    }

    public function supportsDenormalizationData(): array
    {
        return [
            'supports' => [File::class, true],
            'notSupports' => ['testClass', false],
        ];
    }

    /**
     * @dataProvider supportsNormalizationData
     */
    public function testSupportsNormalization($data, $isSupport): void
    {
        self::assertEquals($isSupport, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationData(): array
    {
        return [
            'supports' => [new File(), true],
            'wrongObject' => [new \stdClass(), false],
            'notObject' => ['test', false],
        ];
    }

    public function testNormalize(): void
    {
        $sampleUrl = '/sample/url';
        $sampleUuid = 'sample-uuid';

        $file = $this->getEntity(File::class, ['id' => 1, 'uuid' => $sampleUuid]);
        $this->attachmentManager->expects(self::once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($sampleUrl);

        self::assertEquals(
            [
                'uuid' => $sampleUuid,
                'uri' => $sampleUrl,
            ],
            $this->normalizer->normalize($file)
        );
    }

    public function testNormalizeWhenNoFileId(): void
    {
        $sampleUuid = 'sample-uuid';

        $file = $this->getEntity(File::class, ['uuid' => $sampleUuid]);
        $this->attachmentManager->expects(self::never())
            ->method(self::anything());

        self::assertEquals(
            [
                'uuid' => $sampleUuid,
                'uri' => '',
            ],
            $this->normalizer->normalize($file)
        );
    }

    public function testDenormalizeWhenNoUri(): void
    {
        $sampleUuid = 'sample-uuid';

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);

        self::assertEquals(
            $expectedFile,
            $this->normalizer->denormalize(['uuid' => $sampleUuid], File::class)
        );
    }

    public function testDenormalizeWhenNoUuid(): void
    {
        $sampleUri = __DIR__ . '/FileNormalizerTest.php';
        $expectedFile = new File();
        $expectedFile->setFile(new SymfonyFile($sampleUri, false));

        $this->fileManager->expects(self::never())
            ->method('getReadonlyFilePath');

        $file = $this->normalizer->denormalize(['uri' => $sampleUri], File::class);
        self::assertEquals($expectedFile->getFile(), $expectedFile->getFile());
        self::assertNotEmpty($file->getUuid());
    }

    public function testDenormalizeWithFullUrl(): void
    {
        $sampleUuid = 'sample-uuid';

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);
        $expectedFile->setFile(new SymfonyFile('http://example.org/sample/url', false));

        $this->fileManager->expects(self::never())
            ->method('getReadonlyFilePath');

        $file = $this->normalizer->denormalize(
            ['uri' => 'http://example.org/sample/url', 'uuid' => $sampleUuid],
            File::class
        );

        $expectedFile->setUpdatedAt($file->getUpdatedAt());
        self::assertEquals($expectedFile, $file);
        self::assertEquals($expectedFile->getFile()->getPathname(), $file->getFile()->getPathname());
    }

    public function testDenormalizeWithFullLocalPath(): void
    {
        $sampleUuid = 'sample-uuid';
        $path = __FILE__;

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);
        $expectedFile->setFile(new SymfonyFile($path, false));

        $this->fileManager->expects(self::never())
            ->method('getReadonlyFilePath');

        $file = $this->normalizer->denormalize(
            ['uri' => $path, 'uuid' => $sampleUuid],
            File::class
        );

        $expectedFile->setUpdatedAt($file->getUpdatedAt());
        self::assertEquals($expectedFile, $file);
        self::assertEquals($expectedFile->getFile()->getPathname(), $file->getFile()->getPathname());
    }

    public function testDenormalizeWithRelativePath(): void
    {
        $sampleUuid = 'sample-uuid';
        $path = 'some_file.php';
        $expectedFilePath = 'gaufrette-readonly://import_files/some_file.php';

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);
        $expectedFile->setFile(new SymfonyFile($expectedFilePath, false));

        $this->fileManager->expects(self::once())
            ->method('getReadonlyFilePath')
            ->with($path)
            ->willReturn($expectedFilePath);

        $file = $this->normalizer->denormalize(
            ['uri' => $path, 'uuid' => $sampleUuid],
            File::class
        );

        $expectedFile->setUpdatedAt($file->getUpdatedAt());
        self::assertEquals($expectedFile, $file);
        self::assertEquals($expectedFile->getFile()->getPathname(), $file->getFile()->getPathname());
    }

    public function testDenormalizeWithExternalUrl(): void
    {
        $sampleUuid = 'sample-uuid';
        $url = 'http://example.org/sample/url/filename.pdf';
        $entityClassName = \stdClass::class;
        $fieldName = 'field_name';

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);
        $expectedFile->setFile(new ExternalFile($url));

        $entityFieldConfig = new Config(
            new FieldConfigId('extend', $entityClassName, $fieldName),
            [
                'is_stored_externally' => true,
            ]
        );

        $this->attachmentEntityConfigProvider->expects(self::once())
            ->method('getFieldConfig')
            ->with($entityClassName, $fieldName)
            ->willReturn($entityFieldConfig);

        $this->fileManager->expects(self::never())
            ->method('getReadonlyFilePath');

        $file = $this->normalizer->denormalize(
            ['uri' => $url, 'uuid' => $sampleUuid],
            File::class,
            null,
            [
                'entityName' => $entityClassName,
                'originalFieldName' => $fieldName,
            ]
        );

        $expectedFile->setUpdatedAt($file->getUpdatedAt());
        self::assertEquals($expectedFile, $file);
        self::assertEquals($expectedFile->getFile()->getPathname(), $file->getFile()->getPathname());
    }
}
