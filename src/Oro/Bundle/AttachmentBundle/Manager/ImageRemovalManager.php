<?php

namespace Oro\Bundle\AttachmentBundle\Manager;

use Gaufrette\Adapter\Local;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Remove image file and it's resized/filtered variants.
 */
class ImageRemovalManager implements ImageRemovalManagerInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var ResizedImagePathProviderInterface
     */
    private $resizedImagePathProvider;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var string
     */
    private $fsName;

    /**
     * @var string
     */
    private $mediaCacheDir;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var \Gaufrette\Filesystem
     */
    private $gaufretteFs;

    /**
     * @var array
     * array{resizeImageDirRegex: string, filterImageDirRegex:string}
     */
    private $configuration = [
        'resizeImageDirRegex' => '/^(\/\w+\/\w+\/\d+)\/\d+\/\d+\/\w+/',
        'filterImageDirRegex' => '/^(\/\w+\/\w+\/\w+\/\w+\/\d+)\/\w+/'
    ];

    /**
     * @var null|bool
     */
    private $isGaufretteLocalFs = null;

    /**
     * @param ConfigManager $configManager
     * @param FilterConfiguration $filterConfiguration
     * @param ResizedImagePathProviderInterface $resizedImagePathProvider
     * @param Filesystem $filesystem
     * @param FilesystemMap $filesystemMap
     * @param string $fsName
     * @param string $mediaCacheDir
     * @param string $projectDir
     */
    public function __construct(
        ConfigManager $configManager,
        FilterConfiguration $filterConfiguration,
        ResizedImagePathProviderInterface $resizedImagePathProvider,
        Filesystem $filesystem,
        FilesystemMap $filesystemMap,
        string $fsName,
        string $mediaCacheDir,
        string $projectDir
    ) {
        $this->configManager = $configManager;
        $this->filterConfiguration = $filterConfiguration;
        $this->resizedImagePathProvider = $resizedImagePathProvider;
        $this->filesystem = $filesystem;
        $this->filesystemMap = $filesystemMap;
        $this->fsName = $fsName;
        $this->mediaCacheDir = $mediaCacheDir;
        $this->projectDir = $projectDir;
    }

    /**
     * {@inheritDoc}
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function removeImageWithVariants(File $file)
    {
        $paths = [];
        foreach ($this->getDimensions() as $dimension) {
            $paths[] = $this->getFilePaths($file, $dimension);
        }

        $this->performRemove(array_merge(...$paths));
    }

    /**
     * @param \Gaufrette\Filesystem $fs
     * @param array $paths
     */
    private function performRemove(array $paths): void
    {
        if ($this->isLocalFs()) {
            $this->removeFromLocalFs($paths);
        } else {
            $this->defaultRemove($paths);
        }
    }

    /**
     * @param array $paths
     */
    private function removeFromLocalFs(array $paths): void
    {
        $imageDirs = [];
        foreach ($paths as $path) {
            if ('' === $path) {
                continue;
            }

            $imageDirs[] = sprintf(
                '%s/%s%s',
                $this->projectDir,
                $this->mediaCacheDir,
                $path
            );
        }

        foreach ($imageDirs as $imageDir) {
            if ($this->filesystem->exists($imageDir)) {
                $this->filesystem->remove($imageDir);
            }
        }
    }

    /**
     * @param array $paths
     */
    private function defaultRemove(array $paths): void
    {
        $fs = $this->getGaufretteFs();
        foreach ($paths as $path) {
            if ($fs->has($path)) {
                $fs->delete($path);
            }
        }
    }

    /**
     * @return \Gaufrette\Filesystem
     */
    private function getGaufretteFs(): \Gaufrette\Filesystem
    {
        if ($this->gaufretteFs === null) {
            $this->gaufretteFs = $this->filesystemMap->get($this->fsName);
        }

        return $this->gaufretteFs;
    }

    private function isLocalFs(): bool
    {
        if ($this->isGaufretteLocalFs === null) {
            $adapter = $this->getGaufretteFs()->getAdapter();

            $this->isGaufretteLocalFs = $adapter instanceof Local && !is_subclass_of($adapter, Local::class);
        }

        return $this->isGaufretteLocalFs;
    }

    /**
     * @param File $file
     * @param string $dimension
     * @return string[]
     */
    public function getFilePaths(File $file, string $dimension): array
    {
        $paths = [];
        // Remember original_file_names_enabled state
        $isOriginalFileNamesEnabled = $this->configManager->get('oro_product.original_file_names_enabled');

        // Add files without original_file_names_enabled
        $this->configManager->set('oro_product.original_file_names_enabled', false);
        $this->addResizedPaths($file, $paths);
        $this->addFilteredPaths($file, $dimension, $paths);

        // Add files with original_file_names_enabled
        $this->configManager->set('oro_product.original_file_names_enabled', true);
        $this->addResizedPaths($file, $paths);
        $this->addFilteredPaths($file, $dimension, $paths);

        // Restore original_file_names_enabled state
        $this->configManager->set('oro_product.original_file_names_enabled', $isOriginalFileNamesEnabled);

        return $paths;
    }

    /**
     * @param string $regex
     * @param string $filePath
     * @return string
     */
    private function extractImageDir(string $regex, string $filePath): string
    {
        $matches = [];

        preg_match($regex, $filePath, $matches);
        if (!isset($matches[1])) {
            return '';
        }

        return $matches[1];
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function extractResizeImageDir(string $filePath): string
    {
        return $this->extractImageDir($this->configuration['resizeImageDirRegex'], $filePath);
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function extractFilterImageDir(string $filePath): string
    {
        return $this->extractImageDir($this->configuration['filterImageDirRegex'], $filePath);
    }

    /**
     * @param File $file
     * @param array $paths
     */
    private function addResizedPaths(File $file, array &$paths): void
    {
        $filePath = $this->resizedImagePathProvider->getPathForResizedImage($file, 1, 1);

        if ($this->isLocalFs()) {
            $imageDir = $this->extractResizeImageDir($filePath);
            $paths[md5($imageDir)] = $imageDir;
        } else {
            $paths[md5($filePath)] = $filePath;
        }
    }

    /**
     * @param File $file
     * @param string $dimension
     * @param array $paths
     */
    private function addFilteredPaths(File $file, string $dimension, array &$paths): void
    {
        $filePath = $this->resizedImagePathProvider->getPathForFilteredImage($file, $dimension);

        if ($this->isLocalFs()) {
            $imageDir = $this->extractFilterImageDir($filePath);
            $paths[md5($imageDir)] = $imageDir;
        } else {
            // filtered file path is stored in Gaufrette without leading /
            $paths[md5($filePath)] = ltrim($filePath, '/');
        }
    }

    /**
     * @return string[]
     */
    private function getDimensions(): array
    {
        return array_keys($this->filterConfiguration->all());
    }
}
