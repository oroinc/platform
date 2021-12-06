<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileUrlByUuidProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileUrlProvider;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry;

    private FileUrlByUuidProvider $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->provider = new FileUrlByUuidProvider($this->registry, $this->fileUrlProvider);
    }

    public function testGetFileUrl(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects(self::once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_GET, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            $this->provider->getFileUrl($file->getUuid())
        );
    }

    public function testGetFileUrlFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects(self::never())
            ->method('getFileUrl');

        $this->provider->getFileUrl($file->getUuid());
    }

    public function testGetResizedImageUrlFromDB(): void
    {
        $file = $this->createFile();
        $format = 'sample_format';

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 300, $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            $this->provider->getResizedImageUrl($file->getUuid(), 100, 300, $format)
        );
    }

    public function testGetResizedImageUrlFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects(self::never())
            ->method('getResizedImageUrl');

        $this->provider->getResizedImageUrl($file->getUuid(), 100, 300);
    }

    public function testGetFilteredImageUrlFromDB(): void
    {
        $file = $this->createFile();
        $format = 'sample_format';

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, 'testFilter', $format, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');

        self::assertSame(
            '/url',
            $this->provider->getFilteredImageUrl($file->getUuid(), 'testFilter', $format)
        );
    }

    public function testGetFilteredImageUrlFileNotFound(): void
    {
        $this->expectException(FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects(self::never())
            ->method('getFilteredImageUrl');

        $this->provider->getFilteredImageUrl($file->getUuid(), 'testFilter');
    }

    private function createFile(): File
    {
        $file = new File();
        ReflectionUtil::setId($file, 42);
        $file->setFilename('test.jpg');
        $file->setUuid('testuuid-uuid-uuid-testuuid');

        return $file;
    }

    private function assertLoadFileFromDB(File $file): void
    {
        $repository = $this->getMockBuilder(FileRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByUuid'])
            ->getMock();

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneByUuid')
            ->with($file->getUuid())
            ->willReturn($file);
    }

    private function assertLoadNotExistFile(File $file): void
    {
        $repository = $this->getMockBuilder(FileRepository::class)
            ->disableOriginalConstructor()
            ->addMethods(['findOneByUuid'])
            ->getMock();

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findOneByUuid')
            ->with($file->getUuid())
            ->willReturn(null);
    }
}
