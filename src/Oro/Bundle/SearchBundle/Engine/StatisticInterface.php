<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Contains methods that allow to get statistical metrics by search query
 */
interface StatisticInterface
{
    /**
     * @param Query $query
     *
     * @return array
     * [
     *  <EntityFQCN> => <DocumentsCount>
     * ]
     */
    public function getDocumentsCountGroupByEntityFQCN(Query $query): array;
}
