<?php

namespace Oro\Bundle\OrganizationBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\AbstractFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\OrganizationBundle\Form\Type\Filter\BusinessUnitChoiceFilterType;

class BusinessUnitChoiceFilter extends AbstractFilter
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * Constructor
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility        $util
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $registry
    ) {
        parent::__construct($factory, $util);
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return BusinessUnitChoiceFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata[FilterUtility::TYPE_KEY] = 'choice-tree';
        $metadata['data'] = $this->params['options']['data'];
        return $metadata;
    }

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

    public function parseData($data)
    {
        $data['value'] = explode(',', $data['value']);
        return $data;
    }
}
