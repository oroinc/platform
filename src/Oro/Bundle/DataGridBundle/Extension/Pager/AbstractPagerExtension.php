<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

/**
 * Abstract pager extension
 */
abstract class AbstractPagerExtension extends AbstractExtension
{
    /**
     * Soft limit to prevent unlimited results from index
     */
    protected const SOFT_LIMIT = 1000;

    /**
     * @var PagerInterface
     */
    protected $pager;

    public function __construct(PagerInterface $pager)
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
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        if ($this->getParameters()->has(PagerInterface::PAGER_ROOT_PARAM)) {
            $page = $this->getOr(PagerInterface::PAGE_PARAM, 0);
            if (!is_numeric($page) || $page <= 0) {
                $this->getParameters()
                    ->mergeKey(PagerInterface::PAGER_ROOT_PARAM, [PagerInterface::PAGE_PARAM => 1]);
            }

            $currentPageSize = $this->getOr(PagerInterface::PER_PAGE_PARAM, 0);
            $defaultPageSize = $config->offsetGetByPath(ToolbarExtension::PAGER_DEFAULT_PER_PAGE_OPTION_PATH);
            $pageSizeItems = $config->offsetGetByPath(ToolbarExtension::PAGER_ITEMS_OPTION_PATH, []);

            $exist = array_filter(
                $pageSizeItems,
                static function ($item) use ($currentPageSize) {
                    if (isset($item['size'])) {
                        return $currentPageSize == $item['size'];
                    }

                    return is_numeric($item) ? $currentPageSize == $item : false;
                }
            );

            if (!$exist) {
                $this->getParameters()
                    ->mergeKey(PagerInterface::PAGER_ROOT_PARAM, [PagerInterface::PER_PAGE_PARAM => $defaultPageSize]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $onePage = $config->offsetGetByPath(ToolbarExtension::PAGER_ONE_PAGE_OPTION_PATH, false);
        $totalRecords = $this->pager->getNbResults();

        // if there is more records than soft limit we should show only soft limit records count
        if ($onePage && $totalRecords > self::SOFT_LIMIT) {
            $totalRecords = self::SOFT_LIMIT;
        }

        $result->setTotalRecords($totalRecords);
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
     * {@inheritdoc}
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

        if (isset($pagerParameters[$paramName]) && '' !== $pagerParameters[$paramName]) {
            return $pagerParameters[$paramName];
        }

        return $default;
    }
}
