<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\InvalidItemException;
use Akeneo\Bundle\BatchBundle\Item\ParseException;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

/**
 * Corresponds for reading csv file line by line using context passed
 */
class CsvFileReader extends AbstractFileReader
{
    /**
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string
     */
    protected $enclosure = '"';

    /**
     * @var string
     */
    protected $escape;

    /**
     * @param ContextRegistry $contextRegistry
     */
    public function __construct(ContextRegistry $contextRegistry)
    {
        parent::__construct($contextRegistry);

        // Please, see CsvFileStreamWriter::__construct for explanation.
        $this->escape = chr(0);
    }

    /**
     * {@inheritdoc}
     */
    public function read($context = null)
    {
        if ($this->isEof()) {
            return null;
        }

        $data = $this->getFile()->fgetcsv();
        if (false !== $data) {
            if (! $context instanceof ContextInterface) {
                $context = $this->getContext();
            }
            $context->incrementReadOffset();
            if (null === $data || [null] === $data) {
                if ($this->isEof()) {
                    return null;
                }

                return [];
            }
            $context->incrementReadCount();

            if ($this->firstLineIsHeader) {
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

                $data = array_combine($this->header, $data);
            }
        } else {
            throw new ParseException('An error occurred while reading the csv.');
        }

        return $data;
    }

    /**
     * @return bool
     */
    protected function isEof()
    {
        if ($this->getFile()->eof()) {
            $this->getFile()->rewind();
            $this->header = null;

            return true;
        }

        return false;
    }

    /**
     * @return \SplFileObject
     */
    protected function getFile()
    {
        if ($this->file instanceof \SplFileObject && $this->file->getFilename() != $this->fileInfo->getFilename()) {
            $this->file = null;
            $this->header = null;
        }
        if (!$this->file instanceof \SplFileObject) {
            $this->file = $this->fileInfo->openFile();
            $this->file->setFlags(
                \SplFileObject::READ_CSV |
                \SplFileObject::READ_AHEAD |
                \SplFileObject::DROP_NEW_LINE
            );
            $this->file->setCsvControl(
                $this->delimiter,
                $this->enclosure,
                $this->escape
            );
            if ($this->firstLineIsHeader && !$this->header) {
                $this->header = $this->file->fgetcsv();
            }
        }

        return $this->file;
    }

    /**
     * @param ContextInterface $context
     * @throws InvalidConfigurationException
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);

        if ($context->hasOption(Context::OPTION_DELIMITER)) {
            $this->delimiter = $context->getOption(Context::OPTION_DELIMITER);
        }

        if ($context->hasOption(Context::OPTION_ENCLOSURE)) {
            $this->enclosure = $context->getOption(Context::OPTION_ENCLOSURE);
        }

        if ($context->hasOption(Context::OPTION_ESCAPE)) {
            $this->escape = $context->getOption(Context::OPTION_ESCAPE);
        }

        if ($context->hasOption(Context::OPTION_FIRST_LINE_IS_HEADER)) {
            $this->firstLineIsHeader = (bool)$context->getOption(Context::OPTION_FIRST_LINE_IS_HEADER);
        }

        if ($context->hasOption(Context::OPTION_HEADER)) {
            $this->header = $context->getOption(Context::OPTION_HEADER);
        }
    }
}
