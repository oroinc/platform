<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;
use Oro\Bundle\UserBundle\Entity\User;

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

        $widgetManager = $this->get('oro_dashboard.widget_manager');

        $changeActive = $this->get('request')->get('change_dashboard', false);

        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('User is not logged in');
        }

        if ($changeActive && !$id) {
            throw new NotFoundHttpException('Incorrect request params');
        }

        $dashboards = $manager->getDashboards();
        $currentDashboard = null;

        if ($changeActive) {
            if (!$manager->setUserActiveDashboard($user, $id)) {
                throw new NotFoundHttpException('Dashboard not found');
            }
        }

        $currentDashboard = $manager->getUserActiveDashboard($user);

        if (!$currentDashboard) {
            throw new NotFoundHttpException('Not found available dashboards');
        }

        $config = $currentDashboard->getConfig();

        $template  = isset($config['twig']) ? $config['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render(
            $template,
            array(
                'dashboards' => $dashboards,
                'dashboard' => $currentDashboard,
                'widgets' => $widgetManager->getAvailableWidgets()
            )
        );
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
