<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileRemoval\FileRemovalManagerConfigInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;

/**
 * A service to remove all files related to a specified File entity.
 */
class FileRemovalManager implements FileRemovalManagerInterface
{
    /** @var FileRemovalManagerConfigInterface */
    private $configuration;

    /** @var FileNamesProviderInterface */
    private $fileNamesProvider;

    /** @var MediaCacheManagerRegistryInterface */
    private $mediaCacheManagerRegistry;

    public function __construct(
        FileRemovalManagerConfigInterface $configuration,
        FileNamesProviderInterface $fileNamesProvider,
        MediaCacheManagerRegistryInterface $mediaCacheManagerRegistry
    ) {
        $this->configuration = $configuration;
        $this->fileNamesProvider = $fileNamesProvider;
        $this->mediaCacheManagerRegistry = $mediaCacheManagerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function removeFiles(File $file): void
    {
        if ($file->getExternalUrl()) {
            // Externally stored files are not present in filesystem.
            return;
        }

        $mediaCacheManager = $this->mediaCacheManagerRegistry->getManagerForFile($file);
        $paths = $this->combineFileNames($this->fileNamesProvider->getFileNames($file));
        foreach ($paths as [$path, $isDir]) {
            if ($isDir) {
                $mediaCacheManager->deleteAllFiles($path);
            } else {
                $mediaCacheManager->deleteFile($path);
            }
        }
    }

    /**
     * @param string[] $fileNames
     *
     * @return array [[file or directory name, is directory flag], ...]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function combineFileNames(array $fileNames): array
    {
        $result = [];
        $directoryMap = [];
        $allowedToUseForSingleFileMap = [];
        foreach ($fileNames as $fileName) {
            $extractResult = $this->extractDirectory($fileName);
            if ($extractResult) {
                [$dirName, $isAllowedToUseForSingleFile] = $extractResult;
                $directoryMap[$dirName][] = $fileName;
                if (!isset($allowedToUseForSingleFileMap[$dirName])) {
                    $allowedToUseForSingleFileMap[$dirName] = $isAllowedToUseForSingleFile;
                } elseif ($isAllowedToUseForSingleFile && !$allowedToUseForSingleFileMap[$dirName]) {
                    $allowedToUseForSingleFileMap[$dirName] = $isAllowedToUseForSingleFile;
                }
            } else {
                $result[] = [$fileName, false];
            }
        }
        foreach ($directoryMap as $dirName => $dirFileNames) {
            if ($allowedToUseForSingleFileMap[$dirName] || count($dirFileNames) > 1) {
                $result[] = [$dirName, true];
            } else {
                foreach ($dirFileNames as $fileName) {
                    $result[] = [$fileName, false];
                }
            }
        }

        return $result;
    }

    /**
     * @param string $fileName
     *
     * @return array|null [directory, is allowed to use for a single file flag]
     */
    private function extractDirectory(string $fileName): ?array
    {
        $extractors = $this->configuration->getConfiguration();
        foreach ($extractors as $extractor) {
            $dir = $extractor->extract($fileName);
            if ($dir) {
                if (!str_ends_with($dir, '/')) {
                    $dir .= '/';
                }

                return [$dir, $extractor->isAllowedToUseForSingleFile()];
            }
        }

        return null;
    }
}
