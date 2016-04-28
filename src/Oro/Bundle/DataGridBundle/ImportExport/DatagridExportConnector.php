<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\PagerInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class DatagridExportConnector implements ItemReaderInterface, \Countable, ContextAwareInterface
{
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
        $this->gridManagerLink   = $gridManagerLink;
        $this->pageSize          = BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE;
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

        $result = null;
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
            $gridData         = $this->getGridData();
            $this->totalCount = $gridData->getTotalRecords();
            $this->sourceData = $gridData->getData();
            $this->offset     = 0;
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

        $this->grid->getParameters()->set(
            PagerInterface::PAGER_ROOT_PARAM,
            [
                PagerInterface::PAGE_PARAM     => $this->page,
                PagerInterface::PER_PAGE_PARAM => $this->pageSize
            ]
        );

        return $this->grid->getData();
    }
}
