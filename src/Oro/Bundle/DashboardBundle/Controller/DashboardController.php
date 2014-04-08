<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\DashboardBundle\Manager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/index/{id}",
     *      name="oro_dashboard_index",
     *      defaults={"id" = ""}
     * )
     */
    public function indexAction($id = null)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');

        /**
         * @todo: change work with session after user state will be implement
         */
        if ($this->get('request')->get('change_dashboard', false)) {
            $this->get('session')->set('saved_dashboard', $id);
        } else {
            $id = $this->get('session')->get('saved_dashboard');
        }

        $dashboards = $manager->getDashboards();
        if (count($dashboards) == 0) {
            return $this->render('OroDashboardBundle:Index:withoutDashboards.html.twig');
        }

        $dashboard = $id ? $dashboards->findById($id) : $dashboards->findByName($manager->getDefaultDashboardName());

        if (!$dashboard) {
            $dashboard = $dashboards->current();
        }

        $config = $dashboard->getConfig();

        $template  = isset($config['twig']) ? $config['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render($template, array('dashboards' => $dashboards, 'dashboard' => $dashboard));
    }

    /**
     * @Route(
     *      "/widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_widget",
     *      requirements={"widget"="[\w-]+", "bundle"="\w+", "name"="[\w-]+"}
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
     *      requirements={"widget"="[\w-]+", "bundle"="\w+", "name"="[\w-]+"}
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
