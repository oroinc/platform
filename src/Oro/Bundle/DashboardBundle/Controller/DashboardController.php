<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\DashboardBundle\Model\Manager;
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

        $changeActive = $this->get('request')->get('change_dashboard', false);

        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (!$user || ($changeActive && !$id)) {
            throw new NotFoundHttpException();
        }

        $dashboards = $manager->getDashboards();
        $currentDashboard = null;

        if ($changeActive) {
            if (!$manager->setUserActiveDashboard($user, $id)) {
                throw new NotFoundHttpException();
            }
        }

        $currentDashboard = $manager->getUserDashboard($user);

        $config = $currentDashboard->getConfig();

        $template  = isset($config['twig']) ? $config['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render($template, array('dashboards' => $dashboards, 'dashboard' => $currentDashboard));
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
            $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget)
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
        /** @var WidgetAttributes $manager */
        $manager = $this->get('oro_dashboard.widget_attributes');

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
