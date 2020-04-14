<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\Repository\FileRepository;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlByUuidProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FileUrlByUuidProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var FileUrlByUuidProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->cache = $this->createMock(CacheProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->provider = new FileUrlByUuidProvider(
            $this->fileUrlProvider,
            $this->cache,
            $this->registry
        );
    }

    public function testGetFileUrlFromCache(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromCache($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_GET, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getFileUrl($file->getUuid())
        );
    }

    public function testGetFileUrlFromDB(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_GET, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getFileUrl($file->getUuid())
        );
    }

    public function testGetFileUrlFileNotFound(): void
    {
        $this->expectException(\Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects($this->never())->method('getFileUrl');

        $this->provider->getFileUrl($file->getUuid());
    }

    public function testGetResizedImageUrlFromCache(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromCache($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 300, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getResizedImageUrl($file->getUuid(), 100, 300)
        );
    }

    public function testGetResizedImageUrlFromDB(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getResizedImageUrl')
            ->with($file, 100, 300, UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getResizedImageUrl($file->getUuid(), 100, 300)
        );
    }

    public function testGetResizedImageUrlFileNotFound(): void
    {
        $this->expectException(\Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects($this->never())->method('getResizedImageUrl');

        $this->provider->getResizedImageUrl($file->getUuid(), 100, 300);
    }


    public function testGetFilteredImageUrlFromCache(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromCache($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, 'testFilter', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getFilteredImageUrl($file->getUuid(), 'testFilter')
        );
    }

    public function testGetFilteredImageUrlFromDB(): void
    {
        $file = $this->createFile();

        $this->assertLoadFileFromDB($file);

        $this->fileUrlProvider->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, 'testFilter', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/url');


        $this->assertSame(
            '/url',
            $this->provider->getFilteredImageUrl($file->getUuid(), 'testFilter')
        );
    }

    public function testGetFilteredImageUrlFileNotFound(): void
    {
        $this->expectException(\Oro\Bundle\AttachmentBundle\Exception\FileNotFoundException::class);
        $file = $this->createFile();

        $this->assertLoadNotExistFile($file);

        $this->fileUrlProvider->expects($this->never())->method('getFilteredImageUrl');

        $this->provider->getFilteredImageUrl($file->getUuid(), 'testFilter');
    }

    /**
     * @return File
     */
    private function createFile(): File
    {
        /** @var File $file */
        $file = $this->getEntity(File::class, [
            'id' => 42,
            'filename' => 'test.jpg',
            'uuid' => 'testuuid-uuid-uuid-testuuid',
        ]);

        return $file;
    }

    /**
     * @param File $file
     */
    private function assertLoadFileFromCache(File $file): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($file->getUuid())
            ->willReturn($file);

        $this->cache->expects($this->never())->method('saveMultiple');
    }

    /**
     * @param File $file
     */
    private function assertLoadFileFromDB(File $file): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($file->getUuid())
            ->willReturn(false);

        $repository = $this->createMock(FileRepository::class);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllForEntityByOneUuid')
            ->with($file->getUuid())
            ->willReturn([
                $file->getUuid() => $file,
                'uuid-2' => 'File 2',
            ]);

        $this->cache->expects($this->once())
            ->method('saveMultiple')
            ->with([
                $file->getUuid() => $file,
                'uuid-2' => 'File 2',
            ]);
    }

    /**
     * @param File $file
     */
    private function assertLoadNotExistFile(File $file): void
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($file->getUuid())
            ->willReturn(false);

        $repository = $this->createMock(FileRepository::class);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(File::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('findAllForEntityByOneUuid')
            ->with($file->getUuid())
            ->willReturn([]);
    }
}
