<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Provide common functional for file readers
 */
abstract class AbstractFileReader extends AbstractReader implements ClosableInterface
{
    /** @var \SplFileInfo */
    protected $fileInfo;

    /** @var array */
    protected $header;

    /** @var bool */
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
        if (!$context->hasOption(Context::OPTION_FILE_PATH)) {
            throw new InvalidConfigurationException(
                sprintf('Configuration of reader must contain "%s".', Context::OPTION_FILE_PATH)
            );
        }

        $this->setFilePath($context->getOption('filePath'));

        if ($context->hasOption(Context::OPTION_FIRST_LINE_IS_HEADER)) {
            $this->firstLineIsHeader = (bool)$context->getOption(Context::OPTION_FIRST_LINE_IS_HEADER);
        }

        if ($context->hasOption(Context::OPTION_HEADER)) {
            $this->header = $context->getOption(Context::OPTION_HEADER);
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws InvalidItemException
     */
    protected function normalizeRow(array $data): array
    {
        if (!$this->firstLineIsHeader) {
            return $data;
        }

        if (count($this->header) !== count($data)) {
            throw new InvalidItemException(
                sprintf(
                    'Expecting to get %d columns, actually got %d.
                        Header contains: %s 
                        Row contains: %s',
                    count($this->header),
                    count($data),
                    print_r($this->header, true),
                    print_r($data, true)
                ),
                $data
            );
        }

        return array_combine($this->header, $data);
    }

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
        $this->header = null;
    }
}
