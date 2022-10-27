<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\AttachmentBundle\Manager\FileRemovalManagerInterface;
use Oro\Bundle\AttachmentBundle\Manager\ImageResizeManagerInterface;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

trait ImageRemovalManagerTestingTrait
{
    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(File::class);
    }

    private function createFileEntity(string $fileName = 'original_attachment.jpg'): File
    {
        /** @var FileManager $fileManager */
        $fileManager = self::getContainer()->get('oro_attachment.file_manager');

        return $fileManager->createFileEntity(__DIR__ . '/../Provider/files/' . $fileName);
    }

    private function saveFileEntity(File $file): void
    {
        $em = $this->getEntityManager();
        $em->persist($file);
        $em->flush();
    }

    private function applyImageFilter(File $file, string $filterName): void
    {
        /** @var ImageResizeManagerInterface $imageResizeManager */
        $imageResizeManager = self::getContainer()->get('oro_attachment.manager.image_resize');
        $imageResizeManager->applyFilter($file, $filterName);
    }

    private function resizeImage(File $file, int $width, int $height): void
    {
        /** @var ImageResizeManagerInterface $imageResizeManager */
        $imageResizeManager = self::getContainer()->get('oro_attachment.manager.image_resize');
        $imageResizeManager->resize($file, $width, $height);
    }

    private function removeFiles(File $file): void
    {
        /** @var FileRemovalManagerInterface $imageRemovalManager */
        $imageRemovalManager = self::getContainer()->get('oro_attachment.manager.image_file_removal');
        $imageRemovalManager->removeFiles($file);
    }

    /**
     * @param File $file
     *
     * @return string[]
     */
    private function getImageFileNames(File $file): array
    {
        /** @var FileNamesProviderInterface $provider */
        $provider = self::getContainer()->get('oro_attachment.tests.provider.image_file_names');
        $imageAllFileNames = $provider->getFileNames($file);

        /** @var MediaCacheManagerRegistryInterface $registry */
        $registry = self::getContainer()->get('oro_attachment.tests.media_cache_manager_registry');
        $mediaCacheManager = $registry->getManagerForFile($file);

        $imageFileNames = array_intersect(
            $mediaCacheManager->findFiles('attachment/filter/'),
            $imageAllFileNames
        );
        $imageFileNames = array_merge(
            $imageFileNames,
            $this->getImageResizeFileNames($mediaCacheManager, $file->getId())
        );
        sort($imageFileNames);

        return $imageFileNames;
    }

    /**
     * @param GaufretteFileManager $mediaCacheManager
     * @param int                  $fileId
     *
     * @return string[]
     */
    private function getImageResizeFileNames(GaufretteFileManager $mediaCacheManager, int $fileId): array
    {
        return $mediaCacheManager->findFiles(sprintf('attachment/resize/%d/', $fileId));
    }

    /**
     * @param File     $file
     * @param string[] $fileNames
     */
    private function assertFilesDoNotExist(File $file, array $fileNames)
    {
        /** @var MediaCacheManagerRegistryInterface $registry */
        $registry = self::getContainer()->get('oro_attachment.tests.media_cache_manager_registry');
        $mediaCacheManager = $registry->getManagerForFile($file);
        $existingFileNames = [];
        foreach ($fileNames as $fileName) {
            if ($mediaCacheManager->hasFile($fileName)) {
                $existingFileNames[] = $fileName;
            }
        }
        if ($existingFileNames) {
            if (count($existingFileNames) === 1) {
                self::fail(sprintf('Failed assert that file %s does not exist.', $existingFileNames[0]));
            } else {
                self::fail(sprintf('Failed assert that files %s do not exist.', implode(', ', $existingFileNames)));
            }
        }
    }
}
