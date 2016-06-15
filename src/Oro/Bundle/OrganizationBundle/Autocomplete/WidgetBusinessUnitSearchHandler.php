<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

class WidgetBusinessUnitSearchHandler extends SearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        $this->indexer->setIsAllowedApplyAcl(false);

        return parent::searchIds($search, $firstResult, $maxResults);
    }
}
