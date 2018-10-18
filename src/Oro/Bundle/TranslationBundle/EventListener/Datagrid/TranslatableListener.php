<?php

namespace Oro\Bundle\TranslationBundle\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;

/**
 * Ensures that receive a "query" from a cache that takes into account the current localization
 */
class TranslatableListener
{
    use TranslatableQueryTrait;

    /**
     * @param OrmResultBefore $event
     */
    public function onResultBefore(OrmResultBefore $event)
    {
        $datagrid = $event->getDatagrid();
        $query = $event->getQuery();
        $dataSource = $datagrid->getDatasource();
        if (!$dataSource instanceof OrmDatasource) {
            return;
        }

        $queryBuilder = $dataSource->getQueryBuilder();
        $entityManager = $queryBuilder->getEntityManager();
        if ($query->hasHint('oro_translation.translatable')) {
            $this->addTranslatableLocaleHint($query, $entityManager);
        }
    }
}
