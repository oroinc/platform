<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
