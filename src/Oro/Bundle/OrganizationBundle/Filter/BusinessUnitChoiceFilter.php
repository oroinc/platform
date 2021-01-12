<?php

namespace Oro\Bundle\OrganizationBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\ChoiceTreeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

/**
 * The filter by business unit for User entity.
 */
class BusinessUnitChoiceFilter extends ChoiceTreeFilter
{
    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        if (count($data['value']) > 1 || (isset($data['value'][0]) && $data['value'][0] != '')) {
            $parameterName = $ds->generateParameterName($this->getName());

            $qb2 = $this->registry->getManager()->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u.id')
                ->leftJoin('u.businessUnits', 'bu')
                ->where('bu.id in (:' . $parameterName . ')')
                ->getQuery()
                ->getDQL();

            $this->applyFilterToClause(
                $ds,
                $this->get(FilterUtility::DATA_NAME_KEY) . ' in (' . $qb2 . ')'
            );

            if ($this->isValueRequired($data['type'])) {
                $ds->setParameter($parameterName, $data['value']);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        if (!$metadata['autocomplete_alias']) {
            $metadata['autocomplete_alias'] = 'business_units_tree_search_handler';
        }

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function parseData($data)
    {
        return parent::parseData($data);
    }
}
