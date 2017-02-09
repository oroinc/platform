<?php

namespace Oro\Bundle\DataGridBundle\Tools;

use Symfony\Component\Routing\RouterInterface;

class FilteredDatagridRouteHelper implements DatagridAwareRouteHelperInterface
{
    /**
     * @var string $gridRouteName
     */
    protected $gridRouteName;

    /**
     * @var string $gridName
     */
    protected $gridName;

    /**
     * @var DatagridRouteHelper
     */
    protected $datagridRouteHelper;

    /**
     * @param string $gridRouteName
     * @param string $gridName
     * @param DatagridRouteHelper $datagridRouteHelper
     */
    public function __construct($gridRouteName, $gridName, DatagridRouteHelper $datagridRouteHelper)
    {
        $this->gridRouteName = $gridRouteName;
        $this->gridName = $gridName;
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * Generates URL or URI for the Datagrid filtered by parameters
     *
     * Param 'filters' uses next format ['filterName' => 'filterCriterion', ... , 'filterNameN' => 'filterCriterionN']
     *
     * @param array $filters
     * @param int $referenceType
     *
     * @return string
     */
    public function generate(array $filters = [], $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        $params = ['f' => []];

        foreach ($filters as $filterName => $filterCriteria) {
            $params['f'][$filterName]['value'] = (string)$filterCriteria;
        }

        return $this->datagridRouteHelper->generate(
            $this->gridRouteName,
            $this->gridName,
            $params,
            $referenceType
        );
    }
}
