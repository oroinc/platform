<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\DashboardBundle\Manager;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/index/{name}",
     *      name="oro_dashboard_index",
     *      requirements={"name"="[\w_-]*"},
     *      defaults={"name" = ""}
     * )
     */
    public function indexAction($name = null)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');
        if (empty($name)) {
            $name = $manager->getDefaultDashboardName();
        }
        /**
         * @todo: change work with session after user state will be implement
         */
        if ($this->get('request')->get('change_dashboard', false)) {
            $this->get('session')->set('saved_dashboard', $name);
        } else {
            $name = $this->get('session')->get('saved_dashboard', $manager->getDefaultDashboardName());
        }
        $dashboard = $manager->getDashboard($name);
        $template  = isset($dashboard['twig']) ? $dashboard['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render(
            $template,
            [
                'pageTitle'     => $dashboard['label'],
                'dashboardName' => $name,
                'dashboards'    => $manager->getDashboards(),
                'widgets'       => $manager->getDashboardWidgets($name),
            ]
        );
    }

    /**
     * @Route(
     *      "/widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_widget",
     *      requirements={"widget"="[\w_-]+", "bundle"="\w+", "name"="[\w_-]+"}
     * )
     */
    public function widgetAction($widget, $bundle, $name)
    {
        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name),
            $this->get('oro_dashboard.manager')->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @Route(
     *      "/itemized_widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_itemized_widget",
     *      requirements={"widget"="[\w_-]+", "bundle"="\w+", "name"="[\w_-]+"}
     * )
     */
    public function itemizedWidgetAction($widget, $bundle, $name)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');

        $params = array_merge(
            [
                'items' => $manager->getWidgetItems($widget)
            ],
            $manager->getWidgetAttributesForTwig($widget)
        );

        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name),
            $params
        );
    }
}
