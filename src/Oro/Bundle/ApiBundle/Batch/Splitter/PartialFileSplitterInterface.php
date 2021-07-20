<?php

namespace Oro\Bundle\ApiBundle\Batch\Splitter;

/**
 * The interface for classes that are responsible to split a file to chunks
 * and that provides a possibility to set time limit for the split operation.
 * The implementations of such splitters should stop processing if
 * the specified time limit is exceeded and should provide a possibility to
 * continue processing using another instance of the splitter.
 */
interface PartialFileSplitterInterface extends FileSplitterInterface
{
    /**
     * Indicates whether the source file was split completely or partially.
     */
    public function isCompleted(): bool;

    /**
     * Gets the maximum number of milliseconds that the splitter can spend to split a file.
     *
     * @return int The timeout in milliseconds or -1 for unlimited.
     */
    public function getTimeout(): int;

    /**
     * Sets the maximum number of milliseconds that the splitter can spend to split a file.
     *
     * @param int $milliseconds The timeout in milliseconds or -1 for unlimited
     */
    public function setTimeout(int $milliseconds): void;

    /**
     * Gets the state of the splitter.
     */
    public function getState(): array;

    /**
     * Restores the state of the splitter.
     */
    public function setState(array $data): void;
}
