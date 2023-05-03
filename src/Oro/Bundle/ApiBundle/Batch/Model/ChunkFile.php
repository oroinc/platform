<?php

namespace Oro\Bundle\ApiBundle\Batch\Model;

/**
 * Represents a chunk file for batch operations.
 */
final class ChunkFile
{
    private string $fileName;
    private int $fileIndex;
    private int $firstRecordOffset;
    private ?string $sectionName;

    public function __construct(
        string $fileName,
        int $fileIndex,
        int $firstRecordOffset,
        ?string $sectionName = null
    ) {
        $this->fileName = $fileName;
        $this->fileIndex = $fileIndex;
        $this->firstRecordOffset = $firstRecordOffset;
        $this->sectionName = $sectionName;
    }

    /**
     * Gets the name of the file.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * Gets the index of the file, starting with zero.
     */
    public function getFileIndex(): int
    {
        return $this->fileIndex;
    }

    /**
     * Gets the offset of the first record in the file, starting with zero.
     * If the source file has several root sections the offset is calculated for each section separately.
     */
    public function getFirstRecordOffset(): int
    {
        return $this->firstRecordOffset;
    }

    /**
     * Gets the name of a section from which this chunk file contains records.
     * The chunk file cannot contains records from different sections.
     */
    public function getSectionName(): ?string
    {
        return $this->sectionName;
    }
}
