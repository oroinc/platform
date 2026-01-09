<?php

namespace Oro\Bundle\ImapBundle\Connector\Search;

/**
 * Defines the contract for named search query expression items.
 *
 * This interface extends SearchQueryExprValueInterface to add name management capabilities.
 * Named items represent search criteria that target specific message properties (e.g., FROM, TO, SUBJECT)
 * and allow getting and setting the property name that the search criterion applies to.
 */
interface SearchQueryExprNamedItemInterface extends SearchQueryExprValueInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $name
     */
    public function setName($name);
}
