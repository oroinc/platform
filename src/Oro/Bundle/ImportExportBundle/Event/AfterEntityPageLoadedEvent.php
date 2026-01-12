<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched after a page of entities has been loaded during export.
 *
 * This event allows listeners to inspect and modify the loaded entity objects
 * before they are processed for export. Listeners can access the loaded rows
 * and replace them with modified versions if needed.
 */
class AfterEntityPageLoadedEvent extends Event
{
    /** @var object[] */
    protected $rows;

    /**
     * @param object[] $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return object[]
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param object[] $rows
     */
    public function setRows(array $rows)
    {
        $this->rows = $rows;
    }
}
