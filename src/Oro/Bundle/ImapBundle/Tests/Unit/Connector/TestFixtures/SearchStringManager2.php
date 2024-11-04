<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Connector\TestFixtures;

class SearchStringManager2 implements \Oro\Bundle\ImapBundle\Connector\Search\SearchStringManagerInterface
{
    #[\Override]
    public function isAcceptableItem($name, $value, $match)
    {
        return true;
    }

    #[\Override]
    public function buildSearchString(\Oro\Bundle\ImapBundle\Connector\Search\SearchQueryExpr $searchQueryExpr)
    {
        return '';
    }
}
