<?php

namespace Oro\Bundle\OrganizationBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\ChoiceTreeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

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

        $type =  $data['type'];
        if (count($data['value']) > 1 || (isset($data['value'][0]) && $data['value'][0] != "")) {
            $parameterName = $ds->generateParameterName($this->getName());

            $qb2 = $this->registry->getManager()->getRepository('Oro\Bundle\UserBundle\Entity\User')
                ->createQueryBuilder('u')
                ->select('u.id')
                ->leftJoin('u.businessUnits', 'bu')
                ->where('bu.id in (:'.$parameterName.')')
                ->getQuery()
              ->getDQL();

            $this->applyFilterToClause(
                $ds,
                $this->get(FilterUtility::DATA_NAME_KEY) . ' in ('.$qb2.')'
            );

            if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
                $ds->setParameter($parameterName, $data['value']);
            }
        }
        return true;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function parseData($data)
    {
        $data['value'] = explode(',', $data['value']);
        return $data;
    }
}
