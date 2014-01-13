<?php

namespace Oro\Bundle\DataGridBundle\ImportExport;

use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameters;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Extension\Pager\OrmPagerExtension;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;

class DatagridExportConnector implements ItemReaderInterface, \Countable, ContextAwareInterface
{
    /**
     * @var ManagerInterface
     */
    protected $gridManager;

    /**
     * @var RequestParameters
     */
    protected $requestParameters;

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
     * @param ManagerInterface  $gridManager
     * @param RequestParameters $requestParameters
     */
    public function __construct(
        ManagerInterface $gridManager,
        RequestParameters $requestParameters
    ) {
        $this->gridManager       = $gridManager;
        $this->requestParameters = $requestParameters;
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
                $this->requestParameters->set(
                    OrmPagerExtension::PAGER_ROOT_PARAM,
                    [
                        OrmPagerExtension::PAGE_PARAM => $this->page
                    ]
                );
                $gridData         = $this->grid->getData();
                $this->sourceData = $gridData->offsetGet('data');
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
            $this->grid = $this->gridManager->getDatagrid($context->getOption('gridName'));
        } else {
            throw new InvalidConfigurationException(
                'Configuration of datagrid export reader must contain "gridName".'
            );
        }
    }

    /**
     * @return ContextInterface
     * @throws \LogicException If context is not set
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

            $this->page = 1;
            $this->requestParameters->set(
                OrmPagerExtension::PAGER_ROOT_PARAM,
                [
                    OrmPagerExtension::PAGE_PARAM     => $this->page,
                    OrmPagerExtension::PER_PAGE_PARAM => $this->pageSize
                ]
            );
            $gridData         = $this->grid->getData();
            $this->totalCount = $gridData->offsetGetByPath(OrmPagerExtension::TOTAL_PATH_PARAM);
            $this->sourceData = $gridData->offsetGet('data');
            $this->offset     = 0;
        }
    }
}
