<?php

namespace Oro\Bundle\ReportBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ReportBundle\Grid\StoreSqlExtension;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Captures and stores the executed SQL query in datagrid metadata for debugging and auditing purposes.
 *
 * This listener responds to ORM result events and extracts the prepared SQL statement and its
 * parameters from the executed query. When the datagrid is configured to store SQL (via the
 * {@see StoreSqlExtension}, this listener persists the SQL information in the datagrid's metadata,
 * making it available for display to authorized users who have the `oro_report_view_sql` permission.
 */
class StoreSqlListener
{
    /**
     * Gets prepared SQL and parameters from executed query
     * and stores them in DataGrid config object
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        $config = $event->getDatagrid()->getConfig();
        $path   = sprintf('%s[%s]', MetadataObject::OPTIONS_KEY, StoreSqlExtension::STORE_SQL);
        if ($config->offsetGetByPath($path, false)) {
            $config->offsetAddToArrayByPath(
                StoreSqlExtension::STORED_SQL_PATH,
                [StoreSqlExtension::SQL => QueryUtil::getExecutableSql($event->getQuery())]
            );
        }
    }
}
