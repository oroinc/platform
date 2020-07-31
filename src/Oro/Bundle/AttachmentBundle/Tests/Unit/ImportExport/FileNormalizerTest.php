<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\ImportExport;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\ImportExport\FileNormalizer;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Validator\ConfigFileValidator;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileNormalizerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FileNormalizer */
    protected $normalizer;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $attachmentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $validator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    protected function setUp(): void
    {
        $this->normalizer = new FileNormalizer();

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->fileManager = $this->createMock(FileManager::class);
        $this->validator = $this->createMock(ConfigFileValidator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->normalizer->setAttachmentManager($this->attachmentManager);
        $this->normalizer->setFileManager($this->fileManager);
        $this->normalizer->setValidator($this->validator);
        $this->normalizer->setLogger($this->logger);
    }

    /**
     * @dataProvider supportsDenormalizationData
     */
    public function testSupportsDenormalization($type, $isSupport)
    {
        $this->assertEquals($isSupport, $this->normalizer->supportsDenormalization([], $type));
    }

    public function supportsDenormalizationData()
    {
        return [
            'supports' => [File::class, true],
            'notSupports' => ['testClass', false],
        ];
    }

    /**
     * @dataProvider supportsNormalizationData
     */
    public function testSupportsNormalization($data, $isSupport)
    {
        $this->assertEquals($isSupport, $this->normalizer->supportsNormalization($data));
    }

    public function supportsNormalizationData()
    {
        return [
            'supports' => [new File(), true],
            'wrongObject' => [new \stdClass(), false],
            'notObject' => ['test', false]
        ];
    }

    public function testNormalize()
    {
        $sampleUrl = '/sample/url';
        $sampleUuid = 'sample-uuid';

        $file = $this->getEntity(File::class, ['id' => 1, 'uuid' => $sampleUuid]);
        $this->attachmentManager
            ->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($sampleUrl);

        $this->assertEquals(
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
        $this->attachmentManager
            ->expects($this->never())
            ->method($this->anything());

        $this->assertEquals(
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

        $this->assertEquals(
            $expectedFile,
            $this->normalizer->denormalize(['uuid' => $sampleUuid], File::class)
        );
    }

    public function testDenormalizeWhenNoUuid(): void
    {
        $sampleUri = '/sample/uri';
        $expectedFile = new File();
        $expectedFile->setFile(new SymfonyFile($sampleUri, false));

        $file = $this->normalizer->denormalize(['uri' => $sampleUri], File::class);
        $this->assertEquals($expectedFile->getFile(), $expectedFile->getFile());
        $this->assertNotEmpty($file->getUuid());
    }

    /**
     * @dataProvider denormalizeWhenUriDataProvider
     *
     * @param string $filesDir
     * @param string $uri
     * @param string $expectedUri
     */
    public function testDenormalizeWhenUri(string $filesDir, string $uri, string $expectedUri): void
    {
        $sampleUuid = 'sample-uuid';

        $expectedFile = new File();
        $expectedFile->setUuid($sampleUuid);
        $expectedFile->setFile(new SymfonyFile($expectedUri, false));

        $this->normalizer->setFilesDir($filesDir);

        $file = $this->normalizer->denormalize(['uri' => $uri, 'uuid' => $sampleUuid], File::class);

        $this->assertEquals($expectedFile, $file);
        $this->assertEquals($expectedFile->getFile()->getPathname(), $file->getFile()->getPathname());
    }

    /**
     * @return array
     */
    public function denormalizeWhenUriDataProvider(): array
    {
        return [
            [
                'filesDir' => 'var/import_export/files',
                'uri' => '/sample/url',
                'expectedUri' => '/sample/url',
            ],
            [
                'filesDir' => 'var/import_export/files/',
                'uri' => 'sample/url',
                'expectedUri' => 'var/import_export/files/sample/url',
            ],
            [
                'filesDir' => 'var/import_export/files/',
                'uri' => 'http://example.org/sample/url',
                'expectedUri' => 'http://example.org/sample/url',
            ],
        ];
    }
}
