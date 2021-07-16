<?php

namespace Oro\Bundle\OrganizationBundle\Filter;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\ChoiceTreeFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * The filter by business unit for User entity.
 */
class BusinessUnitChoiceFilter extends ChoiceTreeFilter
{
    /** @var ManagerRegistry */
    private $doctrine;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher,
        ManagerRegistry $doctrine
    ) {
        parent::__construct($factory, $util, $router, $eventDispatcher);
        $this->doctrine = $doctrine;
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

        if (count($data['value']) > 1 || (isset($data['value'][0]) && $data['value'][0] != '')) {
            $parameterName = $ds->generateParameterName($this->getName());

            $dql = $this->doctrine->getManager()->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('u.id')
                ->leftJoin('u.businessUnits', 'bu')
                ->where('bu.id in (:' . $parameterName . ')')
                ->getQuery()
                ->getDQL();

            $this->applyFilterToClause(
                $ds,
                $this->get(FilterUtility::DATA_NAME_KEY) . ' in (' . $dql . ')'
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
}
