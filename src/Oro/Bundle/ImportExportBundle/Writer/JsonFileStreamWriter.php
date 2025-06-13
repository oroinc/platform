<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;

/**
 * The base class to write JSON to a file.
 */
abstract class JsonFileStreamWriter extends FileStreamWriter
{
    /** @var resource|null */
    private $fileHandle;
    private bool $prettyPrint = false;

    /**
     * Sets a flag indicates whether the writer should produce pretty-printed output.
     * @see JSON_PRETTY_PRINT
     */
    public function setPrettyPrint(bool $prettyPrint): void
    {
        $this->prettyPrint = $prettyPrint;
    }

    #[\Override]
    public function write(array $items): void
    {
        $isFileAlreadyOpened = null !== $this->fileHandle;
        $itemIndex = 0;
        foreach ($items as $item) {
            if ($isFileAlreadyOpened || $itemIndex > 0) {
                $this->writeToFile(',');
            }
            $this->writeItem($item);
            $itemIndex++;
        }
        $this->flushOutput();
    }

    /**
     * Closes an open file.
     */
    #[\Override]
    public function close(): void
    {
        if ($this->fileHandle) {
            $this->writeToFile(($this->prettyPrint ? "\n" : '') . ']');
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
    }

    /**
     * Opens a file.
     *
     * @return resource A file pointer resource
     */
    abstract protected function open();

    /**
     * Gets file resource.
     *
     * @return resource A file pointer resource
     */
    protected function getFile()
    {
        if (null === $this->fileHandle) {
            $this->fileHandle = $this->open();
            $this->writeToFile('[' . ($this->prettyPrint ? "\n" : ''));
        }

        return $this->fileHandle;
    }

    /**
     * Flushes the output to a file.
     */
    protected function flushOutput(): void
    {
        if (null !== $this->fileHandle) {
            fflush($this->fileHandle);
        }
    }

    /**
     * Writes JSON item to a file.
     */
    protected function writeItem(array $item): void
    {
        $output = json_encode($item, JSON_THROW_ON_ERROR | ($this->prettyPrint ? JSON_PRETTY_PRINT : 0));
        if ($this->prettyPrint) {
            $output = preg_replace('/^.*$/m', '    $0', $output);
        }
        $this->writeToFile($output);
    }

    protected function writeToFile(string $data): void
    {
        $result = fwrite($this->getFile(), $data);
        if (false === $result) {
            throw new RuntimeException('An error occurred while writing to the JSON.');
        }
    }
}
