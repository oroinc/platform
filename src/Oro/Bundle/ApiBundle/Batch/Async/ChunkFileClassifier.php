<?php

namespace Oro\Bundle\ApiBundle\Batch\Async;

use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;

/**
 * A class that is used to determine a type of a chunk file.
 */
class ChunkFileClassifier implements ChunkFileClassifierInterface
{
    private string $primaryDataSectionName;
    private ?string $includedDataSectionName;

    public function __construct(string $primaryDataSectionName, string $includedDataSectionName = null)
    {
        $this->primaryDataSectionName = $primaryDataSectionName;
        $this->includedDataSectionName = $includedDataSectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function isPrimaryData(ChunkFile $file): bool
    {
        return $file->getSectionName() === $this->primaryDataSectionName;
    }

    /**
     * {@inheritDoc}
     */
    public function isIncludedData(ChunkFile $file): bool
    {
        return $this->includedDataSectionName && $file->getSectionName() === $this->includedDataSectionName;
    }
}
