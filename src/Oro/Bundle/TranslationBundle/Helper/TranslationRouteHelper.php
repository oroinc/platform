<?php

namespace Oro\Bundle\TranslationBundle\Helper;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;

class TranslationRouteHelper
{
    const TRANSLATION_GRID_ROUTE_NAME = 'oro_translation_translation_index';
    const TRANSLATION_GRID_NAME = 'oro-translation-translations-grid';

    /**
     * @var DatagridRouteHelper
     */
    protected $datagridRouteHelper;

    /**
     * TranslationRouteHelper constructor.
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
    public function generate(array $filters, $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        $params = ['f' => []];

        foreach ($filters as $filterName => $filterCriteria) {
            $params['f'][$filterName]['value'] = (string)$filterCriteria;
        }

        return $this->datagridRouteHelper->generate(
            self::TRANSLATION_GRID_ROUTE_NAME,
            self::TRANSLATION_GRID_NAME,
            $params,
            $referenceType
        );
    }
}
