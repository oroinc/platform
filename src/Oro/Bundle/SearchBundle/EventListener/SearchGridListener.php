<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchGridListener
{
    const SELECT_PATH = '[source][query][select]';
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
            $select = (array) $grid->getConfig()->offsetGetByPath(self::SELECT_PATH);
            $query = $datasource->getQuery();
            /** @var Query $query */
            if (!empty($select)) {
                $query->addSelect($select);
            }

            $from = $grid->getConfig()->offsetGetByPath(self::FROM_PATH);
            if (!empty($from)) {
                $query->from($from);
            }
        }
    }
}
