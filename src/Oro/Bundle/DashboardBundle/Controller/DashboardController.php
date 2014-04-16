<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @Route("/dashboard")
 */
class DashboardController extends Controller
{
    /**
     * @Route(
     *      ".{_format}",
     *      name="oro_dashboard_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     *
     * @Acl(
     *      id="oro_dashboard_view",
     *      type="entity",
     *      class="OroDashboardBundle:Dashboard",
     *      permission="VIEW"
     * )
     *
     * @Template
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route(
     *      "/view/{id}.{_format}",
     *      name="oro_dashboard_view",
     *      requirements={"_format"="html|json", "id"="\d+"},
     *      defaults={"_format" = "html"}
     * )
     *
     * @ParamConverter("dashboard", options={"id" = "id"})
     *
     * @AclAncestor("oro_dashboard_view")
     *
     * @Template
     */
    public function viewAction(Dashboard $dashboard)
    {
        return [
            'entity' => $dashboard
        ];
    }

    /**
     * @Route(
     *      "/open/{id}",
     *      name="oro_dashboard_open",
     *      defaults={"id" = ""}
     * )
     */
    public function openAction($id = null)
    {
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

        $dashboards       = $this->getDashboardManager()->getDashboards();
        $currentDashboard = null;

        if ($changeActive) {
            if (!$this->getDashboardManager()->setUserActiveDashboard($user, $id)) {
                throw new NotFoundHttpException('Dashboard not found');
            }
        }

        $currentDashboard = $this->getDashboardManager()->getUserActiveDashboard($user);

        if (!$currentDashboard) {
            return $this->quickLaunchpadAction();
        }

        $config = $currentDashboard->getConfig();

        $template = isset($config['twig']) ? $config['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render(
            $template,
            array(
                'dashboards' => $dashboards,
                'dashboard'  => $currentDashboard,
                'widgets'    => $widgetManager->getAvailableWidgets()
            )
        );
    }

    /**
     * todo: Test action remove it after close https://magecore.atlassian.net/browse/BAP-3267
     *
     * @Route(
     *      "/manage-dashboards",
     *      name="oro_dashboard_management"
     * )
     */
    public function manageAction()
    {
        return new Response('works');
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

    /**
     * @Route(
     *      "/launchpad",
     *      name="oro_dashboard_quick_launchpad"
     * )
     */
    public function quickLaunchpadAction()
    {
        return $this->render(
            'OroDashboardBundle:Index:quickLaunchpad.html.twig',
            [
                'dashboards' => $this->getDashboardManager()->getDashboards(),
            ]
        );
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->get('oro_dashboard.manager');
    }
}
