<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
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
    protected $page = 1;

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

    public function __construct(ServiceLink $gridManagerLink)
    {
        $this->gridManagerLink = $gridManagerLink;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $this->ensureSourceDataInitialized();

        return $this->totalCount !== null ? $this->totalCount : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $this->ensureSourceDataInitialized();

        $result  = null;
        $context = $this->getContext();
        $sourceDataCount = count($this->sourceData);
        if ($this->offset === $sourceDataCount && $this->hasNextPage()) {
            $this->sourceData = $this->getGridData()->getData();
            $this->offset = 0;
        }

        if ($this->offset < $sourceDataCount) {
            $context->incrementReadOffset();
            $context->incrementReadCount();
            $result = $this->sourceData[$this->offset];
            $this->offset++;
        } elseif (!$this->hasNextPage()) {
            // Reader can be used again so reset source data.
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

            $this->page       = $this->getPage();
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
        return (int) $this->getContext()->getOption('pageSize', self::DEFAULT_PAGE_SIZE);
    }

    protected function getExactPage(): ?int
    {
        return $this->getContext()->getOption('exactPage');
    }

    protected function getPage(): int
    {
        return $this->getExactPage() ?? $this->page;
    }

    protected function hasNextPage(): bool
    {
        if ($this->getExactPage()) {
            return false;
        }

        return $this->getContext()->getReadCount() < $this->totalCount;
    }

    protected function incrementPage(): void
    {
        if (!$this->getExactPage()) {
            ++$this->page;
        }
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
            PagerInterface::PAGE_PARAM     => $this->getPage(),
            PagerInterface::PER_PAGE_PARAM => $this->getPageSize()
        ];
        $this->incrementPage();
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
        $this->pageSize = null;
        $this->page = 1;
    }
}
