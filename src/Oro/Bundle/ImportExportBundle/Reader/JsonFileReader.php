<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * Reads JSON from a file.
 */
class JsonFileReader extends AbstractFileReader
{
    private ?string $filePath = null;
    /** @var resource|null */
    private $fileHandle;
    private ?JsonFileParser $parser = null;

    #[\Override]
    public function read($context = null)
    {
        $parser = $this->getParser();
        if ($parser->isEof()) {
            $parser->reset();
            rewind($this->getFile());

            return null;
        }

        // read next item
        $parser->parse();

        if (!$context instanceof ContextInterface) {
            $context = $this->getContext();
        }

        $context->incrementReadOffset();
        if (!$parser->hasItem()) {
            return null;
        }

        $item = $parser->getItem();
        $context->incrementReadCount();

        return $item;
    }

    #[\Override]
    public function close(): void
    {
        if (null !== $this->fileHandle) {
            fclose($this->fileHandle);
        }
        $this->filePath = null;
        $this->fileHandle = null;
        $this->parser = null;
        parent::close();
    }

    /**
     * @return resource A file pointer resource
     */
    private function getFile()
    {
        if (null !== $this->fileHandle && $this->filePath !== $this->fileInfo->getPathname()) {
            $this->filePath = null;
            $this->fileHandle = null;
        }
        if (null === $this->fileHandle) {
            $this->filePath = $this->fileInfo->getPathname();
            $this->fileHandle = fopen($this->filePath, 'r');
        }

        return $this->fileHandle;
    }

    private function getParser(): JsonFileParser
    {
        if (null === $this->parser) {
            $this->parser = new JsonFileParser($this->getFile());
        }

        return $this->parser;
    }
}
