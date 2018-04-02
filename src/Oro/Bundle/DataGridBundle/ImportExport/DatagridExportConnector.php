<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\Item\Support\ClosableInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Datagrid export connector reads items from data grid with configured batch size.
 *
 */
class DatagridExportConnector implements
    ItemReaderInterface,
    \Countable,
    ContextAwareInterface,
    ClosableInterface
{
    const DEFAULT_PAGE_SIZE = 500;

    /**
     * @var ServiceLink
     */
    protected $gridManagerLink;

    /**
     * @var ContextInterface
     */
    protected $context;

    /**
     * @var DatagridInterface
     */
    protected $grid;

    /**
     * @var integer
     */
    protected $pageSize;

    /**
     * @var integer
     */
    protected $page;

    /**
     * @var integer
     */
    protected $totalCount;

    /**
     * @var integer
     */
    protected $offset;

    /**
     * @var array
     */
    protected $sourceData;

    /**
     * @var DatasourceInterface
     */
    protected $gridDataSource;

    /**
     * @param ServiceLink $gridManagerLink
     */
    public function __construct(ServiceLink $gridManagerLink)
    {
        $this->gridManagerLink = $gridManagerLink;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->ensureSourceDataInitialized();

        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->ensureSourceDataInitialized();

        $result  = null;
        $context = $this->getContext();
        if ($context->getReadCount() < $this->totalCount) {
            if ($this->offset === $this->pageSize && $this->page * $this->pageSize < $this->totalCount) {
                $this->page++;
                $this->sourceData = $this->getGridData()->getData();
                $this->offset     = 0;
            }

            if ($this->offset < count($this->sourceData)) {
                $context->incrementReadOffset();
                $context->incrementReadCount();
                $result = $this->sourceData[$this->offset];
                $this->offset++;
            }
        } else {
            // reader can be used again so reset source data
            $this->close();
        }

        return $result;
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
     * @return ContextInterface
     * @throws LogicException If context is not set
     */
    protected function getContext()
    {
        if (null === $this->context) {
            throw new LogicException("The context was not provided.");
        }

        return $this->context;
    }

    /**
     * Makes sure that source data initialized
     */
    protected function ensureSourceDataInitialized()
    {
        if (null === $this->sourceData) {
            if (null === $this->grid) {
                throw new LogicException('Reader must be configured with a grid');
            }

            $this->page       = 1;
            $this->pageSize   = $this->getPageSize();
            $gridData         = $this->getGridData();
            $this->totalCount = $gridData->getTotalRecords();
            $this->sourceData = $gridData->getData();
            $this->offset     = 0;
        }
    }

    /**
     * @return int
     */
    protected function getPageSize()
    {
        if ($this->getContext()->hasOption('pageSize')) {
            return $this->getContext()->getOption('pageSize');
        }

        return self::DEFAULT_PAGE_SIZE;
    }

    /**
     * @return ResultsObject
     */
    protected function getGridData()
    {
        if (null !== $this->gridDataSource) {
            $this->grid->setDatasource(clone $this->gridDataSource);
        } else {
            $this->gridDataSource = clone $this->grid->getDatasource();
        }

        $pagerParameters = [
            PagerInterface::PAGE_PARAM     => $this->page,
            PagerInterface::PER_PAGE_PARAM => $this->pageSize
        ];
        if (null !== $this->totalCount) {
            $pagerParameters[PagerInterface::ADJUSTED_COUNT] = $this->totalCount;
        }
        $this->grid->getParameters()->set(PagerInterface::PAGER_ROOT_PARAM, $pagerParameters);

        return $this->grid->getData();
    }

    public function close()
    {
        $this->sourceData = null;
        $this->gridDataSource = null;
        $this->totalCount = null;
    }
}
