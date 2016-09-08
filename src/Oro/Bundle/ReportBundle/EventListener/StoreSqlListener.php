<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Oro\Component\DoctrineUtils\ORM\QueryUtils;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ReportBundle\Grid\StoreSqlExtension;

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
        $config = $event->getDatagrid()->getConfig();
        $path   = sprintf('%s[%s]', MetadataObject::OPTIONS_KEY, StoreSqlExtension::STORE_SQL);
        if ($config->offsetGetByPath($path, false)) {
            $config->offsetAddToArrayByPath(
                StoreSqlExtension::STORED_SQL_PATH,
                [StoreSqlExtension::SQL => QueryUtils::getExecutableSql($event->getQuery())]
            );
        }
    }
}
