<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResultInterface;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\DTO\SelectedItems;
use Oro\Bundle\DataGridBundle\Extension\MassAction\IterableResultFactoryInterface;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchIterableResult;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * Creates IterableResultInterace for search datasouce to pass to mass action handler through params.
 */
class IterableResultFactory implements IterableResultFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatasourceInterface $dataSource): bool
    {
        return $dataSource instanceof SearchDatasource;
    }

    /**
     * {@inheritdoc}
     */
    public function createIterableResult(
        DatasourceInterface $dataSource,
        ActionConfiguration $actionConfiguration,
        DatagridConfiguration $gridConfiguration,
        SelectedItems $selectedItems
    ): IterableResultInterface {
        /** @var SearchDatasource $dataSource */
        if (!$this->isApplicable($dataSource)) {
            throw new LogicException(
                sprintf('Expecting "%s" datasource type, "%s" given', SearchDatasource::class, get_class($dataSource))
            );
        }

        $identifier = $this->getIdentifierField($actionConfiguration);
        $searchQuery = $this->getSearchQuery($dataSource, $identifier, $selectedItems);

        //prepare query builder
        $searchQuery->getCriteria()->setMaxResults(null);
        $searchQuery->getCriteria()->setFirstResult(null);

        return new SearchIterableResult($searchQuery);
    }

    /**
     * @param SearchDatasource $dataSource
     * @param string $identifierField
     * @param SelectedItems $selectedItems
     * @return SearchQueryInterface
     */
    protected function getSearchQuery(
        SearchDatasource $dataSource,
        $identifierField,
        SelectedItems $selectedItems
    ) {
        $searchQuery = $dataSource->getSearchQuery();

        if ($selectedItems->getValues()) {
            $criteria = $selectedItems->isInset()
                ? Criteria::expr()->in($identifierField, $selectedItems->getValues())
                : Criteria::expr()->notIn($identifierField, $selectedItems->getValues());

            $searchQuery->addWhere($criteria);
        }

        return $searchQuery;
    }

    /**
     * @param ActionConfiguration $actionConfiguration
     * @return string
     */
    protected function getIdentifierField(ActionConfiguration $actionConfiguration)
    {
        $identifier = $actionConfiguration->offsetGetOr('data_identifier');
        if (!$identifier) {
            throw new LogicException('Mass action must define identifier name');
        }

        return $identifier;
    }
}
