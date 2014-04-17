<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetAttributes;

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
     * @param integer $id
     *
     * @Route(
     *      "/index/{id}",
     *      name="oro_dashboard_index",
     *      defaults={"id" = ""}
     * )
     * @Acl(
     *      id="oro_dashboard_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroDashboardBundle:Dashboard"
     * )
     */
    public function openAction($id = null)
    {
        $changeActive = $this->get('request')->get('change_dashboard', false);

        $dashboards = $this->findAllowedDashboards();
        $currentDashboard = $this->findAllowedDashboard($id);

        if ($changeActive) {
            if (!$id) {
                throw new BadRequestHttpException();
            }
            $this->getDashboardManager()->setUserActiveDashboard($currentDashboard, $this->getUser());
        }

        if (!$currentDashboard) {
            return $this->quickLaunchpadAction();
        }

        return $this->render(
            $currentDashboard->getTemplate(),
            array(
                'dashboards' => $dashboards,
                'dashboard' => $currentDashboard,
                'widgets' => $this->get('oro_dashboard.config_provider')->getWidgetConfigs()
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
                'dashboards' => $this->findAllowedDashboards(),
            ]
        );
    }

    /**
     * Get dashboard with granted permission. If dashboard id is not specified, gets current active or default dashboard
     *
     * @param integer|null $id
     * @param string $permission
     * @return DashboardModel|null
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function findAllowedDashboard($id = null, $permission = 'VIEW')
    {
        if ($id) {
            $dashboard = $this->getDashboardManager()->findDashboardModel($id);
            if (!$dashboard) {
                throw new NotFoundHttpException(sprintf('Dashboard #%s is not found.', $id));
            }
            if (!$this->get('oro_security.security_facade')->isGranted($permission, $dashboard->getEntity())) {
                throw new AccessDeniedException(
                    sprintf("Don't have permissions for dashboard #%s", $dashboard->getId())
                );
            }
        } else {
            $dashboard = $this->getDashboardManager()->findUserActiveOrDefaultDashboard($this->getUser());
            if (!$this->get('oro_security.security_facade')->isGranted($permission, $dashboard->getEntity())) {
                $dashboard = null;
            }
        }

        return $dashboard;
    }

    /**
     * @param string $permission
     * @return DashboardModel[]
     */
    protected function findAllowedDashboards($permission = 'VIEW')
    {
        $qb = $this->getDashboardRepository()->createQueryBuilder('dashboard');
        $aclHelper = $this->get('oro_security.acl_helper');

        return $this->getDashboardManager()->getDashboardModels($aclHelper->apply($qb, $permission)->execute());
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->get('oro_dashboard.manager');
    }

    /**
     * @return DashboardRepository
     */
    protected function getDashboardRepository()
    {
        return $this->getDoctrine()->getRepository('OroDashboardBundle:Dashboard');
    }
}
