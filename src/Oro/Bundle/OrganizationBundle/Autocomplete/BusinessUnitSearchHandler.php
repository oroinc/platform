<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\SearchBundle\Engine\Indexer;

class BusinessUnitSearchHandler extends BusinessUnitOwnerSearchHandler
{
    /**
     * @param Indexer $indexer
     * @param array   $config
     * @throws \RuntimeException
     */
    public function initSearchIndexer(Indexer $indexer, array $config)
    {
        parent::initSearchIndexer($indexer, $config);
        $this->indexer->setIsAllowedApplyAcl(false);
        $this->indexer->setSearchHandlerState('business_units_search_handler');
    }
}
