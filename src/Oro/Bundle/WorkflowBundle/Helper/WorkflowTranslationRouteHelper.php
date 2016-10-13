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
     * @param $workflowName
     * @param int $referenceType
     *
     * @return string
     */
    public function generate($workflowName, $referenceType = Router::ABSOLUTE_PATH)
    {
        return $this->datagridRouteHelper->generate(
            self::TRANSLATION_GRID_ROUTE_NAME,
            self::TRANSLATION_GRID_NAME,
            ['f' => ['workflow' => ['value' => $workflowName]]],
            $referenceType
        );
    }
}
