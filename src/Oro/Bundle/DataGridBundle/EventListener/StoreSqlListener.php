<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Doctrine\ORM\Query\Parameter;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Extension\StoreSql\StoreSqlExtension;

class StoreSqlListener
{
    /**
     * Gets prepared SQL and parameters from executed query
     * and stores them in DataGrid config object
     *
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        if ($event->getDatagrid()->getParameters()->get(StoreSqlExtension::SHOW_SQL_SOURCE, false)) {
            $query = $event->getQuery();
            $event->getDatagrid()->getConfig()->offsetAddToArrayByPath(
                StoreSqlExtension::STORED_SQL_PATH,
                [
                    'sql'        => $query->getSQL(),
                    'parameters' => array_map(
                        function (Parameter $parameter) {
                            return [
                                'name'  => $parameter->getName(),
                                'value' => $parameter->getValue(),
                                'type'  => $parameter->getType()
                            ];
                        }
                        , $query->getParameters()->toArray()
                    )
                ]
            );
        }
    }
}
