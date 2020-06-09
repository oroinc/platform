<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Gaufrette\Adapter;
use Gaufrette\Adapter\GridFS;
use Gaufrette\Adapter\Local;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Manager\ImageRemovalManager;
use Oro\Bundle\AttachmentBundle\Model\FileModel;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;

class ImageRemovalManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterConfiguration;

    /**
     * @var ResizedImagePathProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resizedImagePathProvider;

    /**
     * @var Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystem;

    /**
     * @var FilesystemMap|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filesystemMap;

    /**
     * @var string
     */
    private $fsName = 'media_cache';

    /**
     * @var string
     */
    private $mediaCacheDir = 'public/media/cache';

    /**
     * @var string
     */
    private $projectDir = '/var/www';

    /**
     * @var \Gaufrette\Filesystem|\PHPUnit\Framework\MockObject\MockObject
     */
    private $gaufretteFs;

    /**
     * @var ImageRemovalManager
     */
    private $manager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->resizedImagePathProvider = $this->createMock(ResizedImagePathProviderInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->filesystemMap = $this->createMock(FilesystemMap::class);
        $this->gaufretteFs = $this->createMock(\Gaufrette\Filesystem::class);

        $this->manager = new ImageRemovalManager(
            $this->configManager,
            $this->filterConfiguration,
            $this->resizedImagePathProvider,
            $this->filesystem,
            $this->filesystemMap,
            $this->fsName,
            $this->mediaCacheDir,
            $this->projectDir
        );
    }

    public function testRemoveImageWithVariantsLocalFs()
    {
        $file = new FileModel();

        $this->filterConfiguration->expects($this->once())
            ->method('all')
            ->willReturn(['filter1' => []]);

        $this->assertConfigManagerCalls();
        $this->assertGaufretteAdapterCheck(new Local('/tmp'));
        $this->assertResizedImagePathProviderCalls($file);

        $this->filesystem->expects($this->exactly(2))
            ->method('exists')
            ->withConsecutive(
                [$this->projectDir . '/' . $this->mediaCacheDir . '/attachment/resize/171'],
                [
                    $this->projectDir . '/' . $this->mediaCacheDir .
                    '/attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171'
                ]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );
        $this->filesystem->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$this->projectDir . '/' . $this->mediaCacheDir . '/attachment/resize/171'],
                [
                    $this->projectDir . '/' . $this->mediaCacheDir .
                    '/attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171'
                ]
            );

        $this->manager->removeImageWithVariants($file);
    }

    public function testRemoveImageWithVariantsNonLocalFs()
    {
        $file = new FileModel();

        $this->filterConfiguration->expects($this->once())
            ->method('all')
            ->willReturn(['filter1' => []]);

        $this->assertConfigManagerCalls();
        $this->assertGaufretteAdapterCheck($this->createMock(GridFS::class));
        $this->assertResizedImagePathProviderCalls($file);

        $this->gaufretteFs->expects($this->exactly(4))
            ->method('has')
            ->withConsecutive(
                ['/attachment/resize/171/1/1/5e415da649f47611296612.jpg'],
                ['attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171/5e415da649f47611296612.jpg'],
                ['/attachment/resize/171/1/1/5e415da649f47611296612-original.jpg'],
                ['attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171/5e415da649f47611296612_orig.jpg']
            )
            ->willReturn(
                true,
                false,
                true,
                true
            );
        $this->gaufretteFs->expects($this->exactly(3))
            ->method('delete')
            ->withConsecutive(
                ['/attachment/resize/171/1/1/5e415da649f47611296612.jpg'],
                ['/attachment/resize/171/1/1/5e415da649f47611296612-original.jpg'],
                ['attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171/5e415da649f47611296612_orig.jpg']
            );

        $this->manager->removeImageWithVariants($file);
    }

    private function assertConfigManagerCalls()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_product.original_file_names_enabled')
            ->willReturn(false);

        $this->configManager->expects($this->exactly(3))
            ->method('set')
            ->withConsecutive(
                ['oro_product.original_file_names_enabled', false],
                ['oro_product.original_file_names_enabled', true],
                ['oro_product.original_file_names_enabled', false]
            );
    }

    /**
     * @param FileModel $file
     */
    private function assertResizedImagePathProviderCalls(FileModel $file): void
    {
        $this->resizedImagePathProvider->expects($this->exactly(2))
            ->method('getPathForResizedImage')
            ->with($file, 1, 1)
            ->willReturnOnConsecutiveCalls(
                '/attachment/resize/171/1/1/5e415da649f47611296612.jpg',
                '/attachment/resize/171/1/1/5e415da649f47611296612-original.jpg'
            );
        $this->resizedImagePathProvider->expects($this->exactly(2))
            ->method('getPathForFilteredImage')
            ->with($file, 'filter1')
            ->willReturnOnConsecutiveCalls(
                '/attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171/5e415da649f47611296612.jpg',
                '/attachment/resize/filter1/11c00c6d0bd6b875afe655d3c9d4f942/171/5e415da649f47611296612_orig.jpg'
            );
    }

    /**
     * @param Adapter|MockObject $localAdapter
     */
    protected function assertGaufretteAdapterCheck($localAdapter): void
    {
        $this->gaufretteFs->expects($this->once())
            ->method('getAdapter')
            ->willReturn($localAdapter);
        $this->filesystemMap->expects($this->once())
            ->method('get')
            ->with($this->fsName)
            ->willReturn($this->gaufretteFs);
    }
}
