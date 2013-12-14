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
     *      requirements={"name"="[\w_-]+"},
     *      defaults={"name" = ""}
     * )
     */
    public function indexAction($name)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');
        if (empty($name)) {
            $name = $manager->getDefaultDashboardName();
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
     *      "/widget/{bundle}/{name}",
     *      name="oro_dashboard_widget",
     *      requirements={"bundle"="\w+", "name"="[\w_-]+"}
     * )
     */
    public function widgetAction($bundle, $name)
    {
        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name)
        );
    }

    /**
     * @Route(
     *      "/widget/{bundle}/{name}/{widget}",
     *      name="oro_dashboard_itemized_widget",
     *      requirements={"bundle"="\w+", "name"="[\w_-]+", "widget"="[\w_-]+"}
     * )
     */
    public function itemizedWidgetAction($bundle, $name, $widget)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');

        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name),
            [
                'items' => $manager->getWidgetItems($widget)
            ]
        );
    }
}
