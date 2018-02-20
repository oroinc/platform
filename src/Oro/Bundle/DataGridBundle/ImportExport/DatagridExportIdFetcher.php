<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DatagridExportIdFetcher implements ContextAwareInterface
{
    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var DatagridInterface
     */
    protected $grid;

    /**
     * @param ServiceLink $gridManagerLink
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ServiceLink $gridManagerLink, EventDispatcherInterface $eventDispatcher)
    {
        $this->gridManagerLink = $gridManagerLink;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;

        if ($context->hasOption('gridName')) {
            $this->grid = $this->gridManagerLink
                ->getService()
                ->getDatagrid(
                    $context->getOption('gridName'),
                    $context->getOption('gridParameters')
                );
            $context->setValue('columns', $this->grid->getConfig()->offsetGet('columns'));
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export reader must contain "gridName".'
            );
        }
    }

    /**
     * @return array
     */
    public function getGridDataIds()
    {
        /** @var OrmDatasource $dataSource */
        $dataSource = clone $this->grid->getAcceptedDatasource();

        // select only identifier field
        $qb = $dataSource->getQueryBuilder();
        $alias = $qb->getRootAliases()[0];
        $name = $qb->getEntityManager()->getClassMetadata($qb->getRootEntities()[0])->getSingleIdentifierFieldName();

        $this->eventDispatcher->dispatch(
            OrmResultBeforeQuery::NAME,
            new OrmResultBeforeQuery($this->grid, $qb)
        );

        if (!empty($qb->getDQLPart('groupBy')) || !empty($qb->getDQLPart('having'))) {
            return array();
        }

        $qb
            ->indexBy($alias, $alias . '.'. $name)
            ->select($alias . '.' . $name)
            ->setFirstResult(null)
            ->setMaxResults(null);

        if ($this->isOrderedByExpressionOrAlias($qb)) {
            $qb->resetDQLPart('orderBy');
        }

        return array_keys($qb->getQuery()->getArrayResult());
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return bool
     */
    private function isOrderedByExpressionOrAlias(QueryBuilder $queryBuilder): bool
    {
        $orderByParts = $queryBuilder->getDQLPart('orderBy');
        if ($orderByParts === null) {
            return false;
        }

        $aliases = $queryBuilder->getAllAliases();
        /** @var OrderBy $orderByPart */
        foreach ($orderByParts as $orderByPart) {
            foreach ($orderByPart->getParts() as $part) {
                $part = preg_replace('/(ASC|DESC)$/i', '', $part);
                if (!$this->isOrderedByTableField($part, $aliases)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * This function handles only not quoted(delimited) identifiers (i.e. without special characters).
     *
     * @param string $orderBy
     * @param array $aliases
     * @return bool
     */
    private function isOrderedByTableField(string $orderBy, array $aliases): bool
    {
        $parts = explode('.', trim($orderBy));
        if (count($parts) !== 2) {
            return false;
        }

        list($tableName, $fieldName) = $parts;
        if (!in_array($tableName, $aliases, true)) {
            return false;
        }

        return preg_match('/^[\w_\$]+$/', $fieldName);
    }
}
