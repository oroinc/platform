<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Mode\ModeExtension;
use Oro\Bundle\DataGridBundle\Extension\Pager\Orm\Pager;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

/**
 * Class OrmPagerExtension
 * @package Oro\Bundle\DataGridBundle\Extension\Pager
 *
 * Responsibility of this extension is to apply pagination on query for ORM datasource
 */
class OrmPagerExtension extends AbstractExtension
{
    /** @var Pager */
    protected $pager;

    /**
     * @param Pager $pager
     */
    public function __construct(Pager $pager)
    {
        $this->pager = $pager;
    }

    /**
     * Prototype object
     */
    public function __clone()
    {
        $this->pager = clone $this->pager;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        // enabled by default for ORM datasource
        $disabled = $this->getOr(PagerInterface::DISABLED_PARAM, false)
            || $config->offsetGetByPath(ToolbarExtension::TOOLBAR_PAGINATION_HIDE_OPTION_PATH, false);

        return !$disabled && $config->getDatasourceType() == OrmDatasource::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        if ($datasource instanceof OrmDatasource) {
            $this->pager->setQueryBuilder($datasource->getQueryBuilder());
            $this->pager->setSkipAclCheck($config->isDatasourceSkipAclApply());
            $this->pager->setAclPermission($config->getDatasourceAclApplyPermission());
            $this->pager->setSkipCountWalker(
                $config->offsetGetByPath(DatagridConfiguration::DATASOURCE_SKIP_COUNT_WALKER_PATH)
            );
        }

        if ($config->offsetGetByPath(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false) ||
            $config->offsetGetByPath(ModeExtension::MODE_OPTION_PATH) === ModeExtension::MODE_CLIENT
        ) {
            // no restrictions applied
            $this->pager->setPage(0);
            $this->pager->setMaxPerPage(0);
        } else {
            $this->pager->setPage($this->getOr(PagerInterface::PAGE_PARAM, 1));
            $this->pager->setMaxPerPage($this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage));
        }
        $calculatedCount = $this->getOr(PagerInterface::CALCULATED_COUNT);

        if (null !== $calculatedCount && is_int($calculatedCount) && $calculatedCount >= 0) {
            $this->pager->setCalculatedNbResults($calculatedCount);
        }
        $this->pager->init();
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $result->setTotalRecords($this->pager->getNbResults());
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $defaultPage = 1;
        $defaultPerPage = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH, 10);

        $initialState = [
            'currentPage' => $defaultPage,
            'pageSize' => $defaultPerPage
        ];
        $state = [
            'currentPage' => $this->getOr(PagerInterface::PAGE_PARAM, $defaultPage),
            'pageSize' => $this->getOr(PagerInterface::PER_PAGE_PARAM, $defaultPerPage)
        ];

        $data->offsetAddToArray('initialState', $initialState);
        $data->offsetAddToArray('state', $state);
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        // Pager should proceed closest to end of accepting chain
        return -240;
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $pager = [];

            if (array_key_exists(PagerInterface::MINIFIED_PAGE_PARAM, $minifiedParameters)) {
                $pager[PagerInterface::PAGE_PARAM] = $minifiedParameters[PagerInterface::MINIFIED_PAGE_PARAM];
            }
            if (array_key_exists(PagerInterface::MINIFIED_PER_PAGE_PARAM, $minifiedParameters)) {
                $pager[PagerInterface::PER_PAGE_PARAM] = $minifiedParameters[PagerInterface::MINIFIED_PER_PAGE_PARAM];
            }

            $parameters->set(PagerInterface::PAGER_ROOT_PARAM, $pager);
        }

        parent::setParameters($parameters);
    }

    /**
     * Get param or return specified default value
     *
     * @param string $paramName
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getOr($paramName, $default = null)
    {
        $pagerParameters = $this->getParameters()->get(PagerInterface::PAGER_ROOT_PARAM, []);

        return isset($pagerParameters[$paramName]) ? $pagerParameters[$paramName] : $default;
    }
}
