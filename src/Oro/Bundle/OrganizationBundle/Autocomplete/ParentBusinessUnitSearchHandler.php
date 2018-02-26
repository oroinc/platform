<?php

namespace Oro\Bundle\OrganizationBundle\Autocomplete;

use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;

/**
 * Autocomplete search handler that returns a list of Business units excluding the given Business unit and its children.
 */
class ParentBusinessUnitSearchHandler extends BusinessUnitOwnerSearchHandler
{
    /** @var OwnerTreeProviderInterface */
    private $ownerTreeProvider;

    /**
     * @param OwnerTreeProviderInterface $ownerTreeProvider
     */
    public function setOwnerTreeProvider(OwnerTreeProviderInterface $ownerTreeProvider)
    {
        $this->ownerTreeProvider = $ownerTreeProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIds($search, $firstResult, $maxResults)
    {
        list($searchString, $businessUnitId) = explode(';', $search);
        $searchQuery = $this->indexer->getSimpleSearchQuery(
            $searchString,
            $firstResult,
            $maxResults,
            $this->entitySearchAlias
        );

        $excludedIds = $this->ownerTreeProvider->getTree()->getSubordinateBusinessUnitIds($businessUnitId);
        array_push($excludedIds, $businessUnitId);

        $expr = $searchQuery->getCriteria()->expr();
        $searchQuery->getCriteria()->andWhere($expr->notIn('integer.id', $excludedIds));

        $result = $this->indexer->query($searchQuery);
        $elements = $result->getElements();

        $ids = [];
        foreach ($elements as $element) {
            $ids[] = $element->getRecordId();
        }

        return $ids;
    }
}
