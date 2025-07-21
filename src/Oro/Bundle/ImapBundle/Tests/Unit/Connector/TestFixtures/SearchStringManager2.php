<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures;

use Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExpr;
use Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface;

class SearchStringManager2 implements SearchStringManagerInterface
{
    #[\Override]
    public function isAcceptableItem($name, $value, $match)
    {
        return true;
    }

    #[\Override]
    public function buildSearchString(SearchQueryExpr $searchQueryExpr)
    {
        return '';
    }
}
