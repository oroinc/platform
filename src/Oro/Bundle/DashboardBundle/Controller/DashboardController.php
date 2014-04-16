<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Form\Type\DashboardType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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

        $dashboards = $this->getDashboardManager()->getDashboards();
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
     * @Route("/dashboard-update/{id}", name="oro_dashboard_update", requirements={"id"="\d+"},  defaults={"id"=0})
     * @Template()
     */
    public function updateAction(Dashboard $dashboard)
    {
        return $this->update($dashboard);
    }

    /**
     * @Route("/dashboard-create", name="oro_dashboard_create")
     * @Template("OroDashboardBundle:Dashboard:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Dashboard());
    }

    protected function update($dashboard)
    {
        $form = $this->createForm(new DashboardType(), $dashboard, array());
        return array('entity' => $dashboard, 'form'=> $form->createView());
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
