<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class WorkflowTranslationRouteHelper
{
    const TRANSLATION_GRID_ROUTE_NAME = 'oro_translation_translation_index';
    const TRANSLATION_GRID_NAME = 'oro-translation-translations-grid';

    /**
     * @var DatagridRouteHelper
     */
    protected $datagridRouteHelper;

    /**
     * WorkflowTranslationRouteHelper constructor.
     *
     * @param DatagridRouteHelper $datagridRouteHelper
     */
    public function __construct(DatagridRouteHelper $datagridRouteHelper)
    {
        $this->datagridRouteHelper = $datagridRouteHelper;
    }

    /**
     * @param array $filters
     * @param int $referenceType
     *
     * @return string
     */
    public function generate(array $filters, $referenceType = Router::ABSOLUTE_PATH)
    {
        $f = [];

        foreach ($filters as $filterName => $filterCriteria) {
            $f[$filterName]['value'] = (string)$filterCriteria;
        }

        return $this->datagridRouteHelper->generate(
            self::TRANSLATION_GRID_ROUTE_NAME,
            self::TRANSLATION_GRID_NAME,
            ['f' => $f],
            $referenceType
        );
    }
}
