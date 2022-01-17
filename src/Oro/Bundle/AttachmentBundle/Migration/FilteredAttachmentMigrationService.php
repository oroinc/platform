<?php

namespace Oro\Bundle\AttachmentBundle\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Gaufrette\Adapter\Local;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;

/**
 * Migrate filtered attachments to new directory structure
 * @internal
 */
class FilteredAttachmentMigrationService implements FilteredAttachmentMigrationServiceInterface
{
    private const BATCH_SIZE = 1000;

    /**
     * @var FilesystemMap
     */
    private $filesystemMap;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var ImageFilterLoader
     */
    private $filterLoader;

    /**
     * @var string
     */
    private $fsName;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    public function __construct(
        FilesystemMap $filesystemMap,
        FilterConfiguration $filterConfiguration,
        ImageFilterLoader $filterLoader,
        string $fsName,
        string $directory
    ) {
        $this->filesystemMap = $filesystemMap;
        $this->filterConfiguration = $filterConfiguration;
        $this->fsName = $fsName;
        $this->filterLoader = $filterLoader;
        $this->directory = $directory;
    }

    /**
     * {@inheritDoc}
     */
    public function setManager(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritDoc}
     */
    public function migrate(string $fromPrefix, string $toPrefix)
    {
        $fs = $this->filesystemMap->get($this->fsName);
        if (!$fs->isDirectory($fromPrefix)) {
            return;
        }

        $processedFiles = [];
        $processed = 0;
        $filterPathMap = $this->getFilterPathMap();
        $filterNames = array_keys($filterPathMap);
        foreach ($this->getImageFileIdsIterator() as $fileId) {
            $processedFiles[] = $fileId;
            foreach ($filterNames as $filterName) {
                $filterDirPrefix = $fromPrefix . '/' . $fileId . '/' . $filterName;
                if (!$fs->isDirectory($filterDirPrefix)) {
                    continue;
                }

                foreach ($this->getFilesInFolder($fs, $filterDirPrefix) as $oldFilePath) {
                    $fileName = str_replace($filterDirPrefix . '/', '', $oldFilePath);
                    $newFilePath = $toPrefix . '/' . $filterPathMap[$filterName] . '/' . $fileId . '/' . $fileName;
                    if (!$fs->has($newFilePath)) {
                        $fs->rename($oldFilePath, $newFilePath);
                    }
                }

                if (++$processed === self::BATCH_SIZE) {
                    $this->clear($fromPrefix, $processedFiles);
                    $processedFiles = [];
                    $processed = 0;
                }
            }
        }

        if ($processed > 0) {
            $this->clear($fromPrefix, $processedFiles);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $prefix, array $subfolders)
    {
        $fs = $this->filesystemMap->get($this->fsName);
        $adapter = $fs->getAdapter();
        foreach ($subfolders as $subfolder) {
            $adapter->delete($prefix . '/' . $subfolder);
        }
    }

    /**
     * @return \Iterator
     */
    private function getImageFileIdsIterator()
    {
        $qb = $this->manager->createQueryBuilder();
        $qb->from(File::class, 'f')
            ->select('f.id')
            ->andWhere($qb->expr()->like('f.mimeType', ':imageMimeType'))
            ->setParameter('imageMimeType', 'image/%');

        $query = $qb->getQuery();

        $identifierHydrationMode = 'IdentifierHydrator';
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);
        $query->setHydrationMode($identifierHydrationMode);

        $iterator = new BufferedQueryResultIterator($query);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }

    private function getFilterPathMap(): array
    {
        $filterMap = [];
        $this->filterLoader->forceLoad();
        foreach ($this->filterConfiguration->all() as $filterName => $config) {
            $filterMap[$filterName] = $filterName . '/' . md5(json_encode($config));
        }

        return $filterMap;
    }

    /**
     * @param \Gaufrette\Filesystem $fs
     * @param string $filterDirPrefix
     * @return iterable|string[]
     */
    private function getFilesInFolder(\Gaufrette\Filesystem $fs, string $filterDirPrefix)
    {
        if ($fs->getAdapter() instanceof Local) {
            // For local adapter do not use listKeys as it iterates over all files and directories within root dir
            // Which requires a lot of memory and fails on big amount of files
            try {
                $filesIterator = new \FilesystemIterator(
                    $this->directory . '/' . $filterDirPrefix,
                    \FilesystemIterator::SKIP_DOTS
                    | \FilesystemIterator::UNIX_PATHS
                    | \FilesystemIterator::CURRENT_AS_PATHNAME
                );
                foreach ($filesIterator as $item) {
                    yield str_replace($this->directory . '/', '', $item);
                }
            } catch (\Exception $e) {
                return [];
            }
        } else {
            // Support non-local adapters
            return $fs->listKeys($filterDirPrefix)['keys'];
        }
    }
}
