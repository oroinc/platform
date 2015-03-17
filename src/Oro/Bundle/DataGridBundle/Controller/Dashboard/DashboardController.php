<?php

namespace Oro\Bundle\DataGridBundle\Controller\Dashboard;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/widget/{widget}/{gridName}",
     *      name="oro_datagrid_dashboard_grid",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     * @Template("OroDataGridBundle:Dashboard:grid.html.twig")
     *
     * @param string $gridName
     *
     * @return Response
     */
    public function gridAction($widget, $gridName)
    {
        $params = $this->getRequest()->get('params', []);
        $renderParams = $this->getRequest()->get('renderParams', []);

        $viewId = $this->getWidgetConfigs()->getCurrentWidgetOptions()->get('gridView');
        if ($viewId && null !== $view = $this->findView($viewId)) {
            $params = array_merge($params, [
                ParameterBag::ADDITIONAL_PARAMETERS => [
                    GridViewsExtension::VIEWS_PARAM_KEY => $viewId
                ],
                '_filter' => $view->getFiltersData(),
                '_sort_by' => $view->getSortersData(),
            ]);
        }

        return array_merge([
            'gridName'     => $gridName,
            'params'       => $params,
            'renderParams' => $renderParams,
        ], $this->getWidgetConfigs()->getWidgetAttributesForTwig($widget));
    }

    /**
     * @return WidgetConfigs
     */
    protected function getWidgetConfigs()
    {
        return $this->get('oro_dashboard.widget_configs');
    }

    /**
     * @param int $id
     *
     * @return GridView
     */
    protected function findView($id)
    {
        return $this->getDoctrine()->getRepository('OroDataGridBundle:GridView')->find($id);
    }
}
