<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

abstract class AbstractFileReader extends AbstractReader implements ClosableInterface
{
    /**
     * @var \SplFileInfo
     */
    protected $fileInfo;

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var array
     */
    protected $header;

    /**
     * @var bool
     */
    protected $firstLineIsHeader = true;

    /**
     * @param string $filePath
     * @throws InvalidArgumentException
     */
    protected function setFilePath($filePath)
    {
        $this->fileInfo = new \SplFileInfo($filePath);

        if (!$this->fileInfo->isFile()) {
            throw new InvalidArgumentException(sprintf('File "%s" does not exists.', $filePath));
        } elseif (!$this->fileInfo->isReadable()) {
            throw new InvalidArgumentException(sprintf('File "%s" is not readable.', $this->fileInfo->getRealPath()));
        }
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if (! $context->hasOption('filePath')) {
            throw new InvalidConfigurationException(
                'Configuration of reader must contain "filePath".'
            );
        } else {
            $this->setFilePath($context->getOption('filePath'));
        }
    }

    /**
     * @return \SplFileObject
     */
    abstract protected function getFile();

    /**
     * @return array|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->file = null;
        $this->header = null;
    }
}
