<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager as DatagridManager;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Component\DoctrineUtils\ORM\Walker\MaterializedViewOutputResultModifier;

/**
 * Batch item reader for the datagrid export working with the materialized view.
 */
class DatagridMaterializedViewReader implements
    ItemReaderInterface,
    ContextAwareInterface,
    ClosableInterface
{
    private DatagridManager $datagridManager;

    private ?ContextInterface $context = null;

    private ?string $materializedViewName = null;

    private ?array $records = null;

    private int $offset = 0;

    public function __construct(DatagridManager $datagridManager)
    {
        $this->datagridManager = $datagridManager;
    }

    public function setImportExportContext(ContextInterface $context): void
    {
        $this->close();

        $this->context = $context;
        $this->materializedViewName = $context->getOption('materializedViewName');
        if (!$this->materializedViewName) {
            throw new InvalidConfigurationException('Context parameter "materializedViewName" cannot be empty');
        }
    }

    public function read(): mixed
    {
        $context = $this->getContext();

        if ($this->records === null) {
            $this->records = $this->getRecords($context);
        }

        if ($this->offset < count($this->records)) {
            $result = $this->records[$this->offset++];

            $context->incrementReadOffset();
            $context->incrementReadCount();
        }

        return $result ?? null;
    }

    public function onResultBefore(OrmResultBefore $ormResultBefore): void
    {
        if ($this->context === null) {
            return;
        }

        $context = $this->getContext();
        if ($ormResultBefore->getDatagrid()->getName() !== $context->getOption('gridName')) {
            return;
        }

        // Adds the query hint that modifies the query to use the materialized view instead of a regular table.
        $ormResultBefore
            ->getQuery()
            ->setHint(
                MaterializedViewOutputResultModifier::USE_MATERIALIZED_VIEW,
                $this->materializedViewName
            )
            ->setFirstResult((int)$context->getOption('rowsOffset'))
            ->setMaxResults((int)$context->getOption('rowsLimit'));
    }

    private function getRecords(ContextInterface $context): array
    {
        $datagrid = $this->getDatagrid($context);

        // Disables {@see Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension} because the query will be
        // provided with offset and limit directly in ::onResultBefore.
        // Besides, it eliminates unwanted count query.
        $datagrid->getParameters()->set(PagerInterface::PAGER_ROOT_PARAM, [PagerInterface::DISABLED_PARAM => true]);

        return $datagrid->getData()->getData();
    }

    private function getDatagrid(ContextInterface $context): DatagridInterface
    {
        if (!$context->getOption('gridName')) {
            throw new InvalidConfigurationException('Context parameter "gridName" cannot be empty');
        }

        return $this->datagridManager->getDatagrid(
            $context->getOption('gridName'),
            $context->getOption('gridParameters', [])
        );
    }

    private function getContext(): ContextInterface
    {
        if (null === $this->context) {
            throw new LogicException(
                sprintf(
                    'The export context was expected to be defined at this point. '
                    . 'Make sure %s::setImportExportContext() is called.',
                    __CLASS__
                )
            );
        }

        return $this->context;
    }

    public function close(): void
    {
        $this->offset = 0;
        $this->records = null;
        $this->materializedViewName = null;
        $this->context = null;
    }
}
