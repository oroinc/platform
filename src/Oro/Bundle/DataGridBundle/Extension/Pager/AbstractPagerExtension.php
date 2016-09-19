<?php

namespace Oro\Bundle\DataGridBundle\Extension\Pager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

abstract class AbstractPagerExtension extends AbstractExtension
{
    /** @var AbstractPager */
    protected $pager;

    /**
     * @param AbstractPager $pager
     */
    public function __construct(AbstractPager $pager)
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
