<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEntityFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Symfony\Component\Form\FormFactoryInterface;

class SearchEntityFilter extends EntityFilter
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * {@inheritDoc}
     *
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(FormFactoryInterface $factory, FilterUtility $util, DoctrineHelper $doctrineHelper)
    {
        parent::__construct($factory, $util);

        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return SearchEntityFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        if (isset($params['class'])) {
            $params[FilterUtility::FORM_OPTIONS_KEY]['class'] = $params['class'];

            unset($params['class']);
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     *
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);

        /** @var Collection $values */
        $values = $data['value'];
        $values = $values->map(
            function ($entity) {
                return $this->doctrineHelper->getSingleEntityIdentifier($entity, false);
            }
        );

        $ds->addRestriction(
            Criteria::expr()->in($fieldName, array_filter($values->toArray())),
            FilterUtility::CONDITION_AND
        );

        return true;
    }
}
