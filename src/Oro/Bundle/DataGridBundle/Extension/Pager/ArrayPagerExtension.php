<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ArrayDatasource\ArrayDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\ArrayDatasource\ArrayPager;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class ArrayPagerExtension extends AbstractPagerExtension
{
    /**
     * @var ArrayPager
     */
    protected $pager;

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // ArrayPagerExtension must be executed after ArraySorterExtension which has parent value - 260
        return -270;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->getDatasourceType() === ArrayDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (!$datasource instanceof ArrayDatasource) {
            throw new UnexpectedTypeException($datasource, ArrayDatasource::class);
        }

        $onePage = $config->offsetGetByPath(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false);
        $mode = $config->offsetGetByPath(ModeExtension::MODE_OPTION_PATH);
        $perPageLimit = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH);
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);
        $perPageCount = $this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage);

        if ((!$perPageLimit && $onePage) || $mode === ModeExtension::MODE_CLIENT) {
            $this->pager->setPage(0);
            $this->pager->setMaxPerPage(0);
        } elseif ($onePage && $perPageLimit) {
            $this->pager->setPage(0);
            $this->pager->setMaxPerPage($perPageCount);
        } else {
            $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));
            $this->pager->setMaxPerPage($perPageCount);
        }
        $this->pager->apply($datasource);
    }
}
