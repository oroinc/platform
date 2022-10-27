<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

use Oro\Bundle\ApiBundle\Batch\Model\ChunkFile;
use Oro\Bundle\ApiBundle\Exception\FileSplitterException;
use Oro\Bundle\ApiBundle\Exception\ParsingErrorFileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * The interface for classes that are responsible to split a file to chunks.
 */
interface FileSplitterInterface
{
    /**
     * Splits the file into multiple files.
     *
     * @param string      $fileName        The file name
     * @param FileManager $srcFileManager  The manager that is used to load the given file
     * @param FileManager $destFileManager The manager that is used to store chunks
     *
     * @return ChunkFile[] The list of files the source file was split into
     *
     * @throws FileSplitterException if the splitting of the given file failed
     * @throws ParsingErrorFileSplitterException if the splitting of the given file failed
     *                                           due to the file contains errors that cannot be parsed
     */
    public function splitFile(string $fileName, FileManager $srcFileManager, FileManager $destFileManager): array;

    /**
     * Gets the maximum number of objects that can be saved in a chunk.
     */
    public function getChunkSize(): int;

    /**
     * Sets the maximum number of objects that can be saved in a chunk.
     */
    public function setChunkSize(int $size): void;

    /**
     * Gets the maximum number of objects that can be saved in a chunk for a specific first level sections.
     *
     * @return array [section name => chunk size, ...]
     */
    public function getChunkSizePerSection(): array;

    /**
     * Sets the maximum number of objects that can be saved in a chunk for a specific first level sections.
     *
     * @param array $sizes [section name => chunk size, ...]
     */
    public function setChunkSizePerSection(array $sizes): void;

    /**
     * Gets the template that should be used to build the name of a chunk file.
     */
    public function getChunkFileNameTemplate(): ?string;

    /**
     * Sets the template that should be used to build the name of a chunk file.
     */
    public function setChunkFileNameTemplate(?string $template): void;

    /**
     * Gets the name of a header section.
     */
    public function getHeaderSectionName(): ?string;

    /**
     * Sets the name of a header section.
     */
    public function setHeaderSectionName(?string $name): void;

    /**
     * Gets names of sections to be split.
     * The empty array indicated that any sections should be split.
     * If at least one section to split is specified than the splitter will ignore all other sections.
     *
     * @return string[]
     */
    public function getSectionNamesToSplit(): array;

    /**
     * Sets names of sections to be split.
     * If at least one section to split is specified than the splitter will ignore all other sections.
     *
     * @param string[] $names The names or empty array if any sections should be split
     */
    public function setSectionNamesToSplit(array $names): void;
}
