<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;

class SearchGridListener
{
    const FROM_PATH = '[source][query][from]';

    /**
     * Reads 'from' part in configuration and adds it to the query
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $grid = $event->getDatagrid();
        $datasource = $grid->getDatasource();

        if ($datasource instanceof SearchDatasource) {
            $from = $grid->getConfig()->offsetGetByPath(self::FROM_PATH);
            if (!empty($from)) {
                $datasource->getQuery()->from($from);
            }
        }
    }
}
