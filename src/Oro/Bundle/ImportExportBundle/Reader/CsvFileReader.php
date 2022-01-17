<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\BatchBundle\Exception\ParseException;
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
     * @var \SplFileObject
     */
    private $file;

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
        if (false === $data) {
            throw new ParseException('An error occurred while reading the csv.');
        }

        if (!$context instanceof ContextInterface) {
            $context = $this->getContext();
        }

        $context->incrementReadOffset();
        if (null === $data || [null] === $data) {
            return $this->isEof() ? null : [];
        }
        $context->incrementReadCount();

        return $this->normalizeRow($data);
    }

    /**
     * @return bool
     */
    protected function isEof()
    {
        $file = $this->getFile();
        if ($file->eof()) {
            $file->rewind();
            $this->header = null;

            return true;
        }

        $before = $file->ftell();
        $file->fgetcsv();
        if ($before === $file->ftell()) {
            return true;
        }

        $file->fseek($before);

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
                \SplFileObject::SKIP_EMPTY
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
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->file = null;
        parent::close();
    }
}
