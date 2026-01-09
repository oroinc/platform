<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched during data format conversion in import/export operations.
 *
 * This event allows listeners to intercept and modify data during format conversion,
 * such as when converting between internal entity representations and export formats.
 * Listeners can access the original record and the conversion result, and modify
 * either as needed.
 */
class FormatConversionEvent extends Event
{
    /** @var array */
    protected $record = [];

    /** @var array */
    protected $result = [];

    public function __construct(array $record, array $result = [])
    {
        $this->record = $record;
        $this->result = $result;
    }

    /**
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $record
     *
     * @return $this
     */
    public function setRecord(array $record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * @param array $result
     *
     * @return $this
     */
    public function setResult(array $result)
    {
        $this->result = $result;

        return $this;
    }
}
