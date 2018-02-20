<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Symfony\Component\Routing\RouterInterface;

class TranslationsDatagridRouteHelper
{
    const TRANSLATION_GRID_ROUTE_NAME = 'oro_translation_translation_index';
    const TRANSLATION_GRID_NAME = 'oro-translation-translations-grid';

    /**
     * @var DatagridRouteHelper
     */
    protected $datagridRouteHelper;

    /**
     * @param DatagridRouteHelper $datagridRouteHelper
     */
    public function __construct(DatagridRouteHelper $datagridRouteHelper)
    {
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * Generates URL or URI to "Manage Translations" page with properly configured query string
     * to apply filter criteria.
     *
     * Param 'filters' uses next format ['filterName' => 'filterCriterion', ... , 'filterNameN' => 'filterCriterionN']
     *
     * @param array $filters
     * @param int   $referenceType
     * @param array $filtersType
     *
     * @return string
     */
    public function generate(
        array $filters = [],
        $referenceType = RouterInterface::ABSOLUTE_PATH,
        array $filtersType = []
    ) {
        $params = ['f' => []];

        foreach ($filters as $filterName => $filterCriteria) {
            $params['f'][$filterName]['value'] = (string)$filterCriteria;
            if (isset($filtersType[$filterName])) {
                $params['f'][$filterName]['type'] = (string)$filtersType[$filterName];
            }
        }

        return $this->datagridRouteHelper->generate(
            self::TRANSLATION_GRID_ROUTE_NAME,
            self::TRANSLATION_GRID_NAME,
            $params,
            $referenceType
        );
    }
}
