<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
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
     * @Route(
     *      "/view/{id}.{_format}",
     *      name="oro_dashboard_view",
     *      requirements={"_format"="html|json", "id"="\d+"},
     *      defaults={"_format" = "html"}
     * )
     * @ParamConverter("dashboard", options={"id" = "id"})
     * @AclAncestor("oro_dashboard_view")
     * @Template
     */
    public function viewAction(Dashboard $dashboard)
    {
        return [
            'entity' => $dashboard
        ];
    }

    /**
     * @param integer $id
     *
     * @Route(
     *      "/open/{id}",
     *      name="oro_dashboard_open",
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

        $dashboards = $this->getDashboardManager()->findAllowedDashboards();
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
     * @Route("/dashboard-update/{id}", name="oro_dashboard_update", requirements={"id"="\d+"},  defaults={"id"=0})
     * @Acl(
     *      id="oro_dashboard_update",
     *      type="entity",
     *      class="OroDashboardBundle:Dashboard",
     *      permission="EDIT"
     * )
     *
     * @ParamConverter("dashboard", options={"id" = "id"})
     *
     * @Template()
     */
    public function updateAction(Dashboard $dashboard)
    {
        return $this->update($dashboard);
    }

    /**
     * @Route("/dashboard-create", name="oro_dashboard_create")
     * @Acl(
     *      id="oro_dashboard_create",
     *      type="entity",
     *      class="OroDashboardBundle:Dashboard",
     *      permission="CREATE"
     * )
     * @Template("OroDashboardBundle:Dashboard:update.html.twig")
     */
    public function createAction()
    {
        return $this->update(new Dashboard());
    }

    /**
     * @param Dashboard $dashboard
     * @return mixed
     */
    protected function update(Dashboard $dashboard)
    {
        $form = $this->createForm($this->container->get('oro_dashboard.form.type.edit'), $dashboard, array());
        $request = $this->getRequest();
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                if ($dashboard->getId()) {
                    $dashboard->setUpdatedAt(new \DateTime());
                } else {
                    $dashboard->setCreatedAt(new \DateTime());
                }

                $this->getDoctrine()->getManager()->persist($dashboard);
                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.dashboard.saved_message')
                );

                return $this->get('oro_ui.router')->redirectAfterSave(
                    array(
                        'route' => 'oro_dashboard_update',
                        'parameters' => array('id' => $dashboard->getId()),
                    ),
                    array(
                        'route' => 'oro_dashboard_open',
                        'parameters' => array('id' => $dashboard->getId(), 'change_dashboard' => true),
                    )
                );
            }
        }

        return array('entity' => $dashboard, 'form'=> $form->createView());
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
                'dashboards' => $this->getDashboardManager()->findAllowedDashboards(),
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
