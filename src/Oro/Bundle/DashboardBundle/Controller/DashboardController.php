<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\StateManager;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Provider\WidgetConfigurationFormProvider;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

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
     * @Template
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_dashboard.dashboard_entity.class')
        ];
    }

    /**
     * @param Dashboard $dashboard
     *
     * @Route(
     *      "/view/{id}",
     *      name="oro_dashboard_view",
     *      requirements={"id"="\d+"},
     *      defaults={"id" = "0"}
     * )
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Dashboard $dashboard = null)
    {
        $currentDashboard = $this->findAllowedDashboard($dashboard);

        if (!$currentDashboard) {
            return $this->quickLaunchpadAction();
        }

        if (!$this->getSecurityFacade()->isGranted('VIEW', $currentDashboard->getEntity())) {
            return $this->quickLaunchpadAction();
        }

        $changeActive = $this->get('request')->get('change_dashboard', false);
        if ($changeActive && $dashboard) {
            $this->getDashboardManager()->setUserActiveDashboard(
                $currentDashboard,
                $this->getUser(),
                true
            );
        }

        return $this->render(
            $currentDashboard->getTemplate(),
            [
                'dashboards' => $this->getDashboardManager()->findAllowedDashboards(),
                'dashboard'  => $currentDashboard,
                'widgets'    => $this->get('oro_dashboard.widget_configs')->getWidgetConfigs()
            ]
        );
    }

    /**
     * @Route("/configure/{id}", name="oro_dashboard_configure", requirements={"id"="\d+"})
     * @Method({"GET", "POST"})
     * @Template("OroDashboardBundle:Dashboard:dialog/configure.html.twig")
     */
    public function configureAction(Request $request, Widget $widget)
    {
        if (!$this->getSecurityFacade()->isGranted('EDIT', $widget->getDashboard())) {
            throw new AccessDeniedException();
        }

        $form  = $this->getFormProvider()->getForm($widget->getName());
        $saved = false;

        $form->setData($this->get('oro_dashboard.widget_configs')->getFormValues($widget));

        $form->handleRequest($request);
        if ($form->isValid()) {
            $widget->setOptions($form->getData());
            $this->getEntityManager()->flush();
            $saved = true;
        }

        return [
            'form'       => $form->createView(),
            'formAction' => $request->getRequestUri(),
            'saved'      => $saved
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_dashboard_update", requirements={"id"="\d+"},  defaults={"id"=0})
     * @Acl(
     *      id="oro_dashboard_update",
     *      type="entity",
     *      class="OroDashboardBundle:Dashboard",
     *      permission="EDIT"
     * )
     *
     * @Template()
     */
    public function updateAction(Dashboard $dashboard)
    {
        $dashboardModel = $this->getDashboardManager()->getDashboardModel($dashboard);

        return $this->update($dashboardModel);
    }

    /**
     * @Route("/create", name="oro_dashboard_create")
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
        $dashboardModel = $this->getDashboardManager()->createDashboardModel();

        return $this->update($dashboardModel);
    }

    /**
     * @param DashboardModel $dashboardModel
     * @return mixed
     */
    protected function update(DashboardModel $dashboardModel)
    {
        $form = $this->createForm(
            $this->container->get('oro_dashboard.form.type.edit'),
            $dashboardModel->getEntity(),
            [
                'create_new' => !$dashboardModel->getId()
            ]
        );

        $request = $this->getRequest();
        if ($request->isMethod('POST')) {
            if ($form->submit($request)->isValid()) {
                $this->getDashboardManager()->save($dashboardModel, true);
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.dashboard.saved_message')
                );

                return $this->get('oro_ui.router')->redirect($dashboardModel->getEntity());
            }
        }

        return ['entity' => $dashboardModel, 'form' => $form->createView()];
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
            $this->get('oro_dashboard.widget_configs')->getWidgetAttributesForTwig($widget)
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
        /** @var WidgetConfigs $manager */
        $manager = $this->get('oro_dashboard.widget_configs');

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
     *      "/itemized_data_widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_itemized_data_widget",
     *      requirements={"widget"="[\w-]+", "bundle"="\w+", "name"="[\w-]+"}
     * )
     */
    public function itemizedDataWidgetAction($widget, $bundle, $name)
    {
        /** @var WidgetConfigs $manager */
        $manager = $this->get('oro_dashboard.widget_configs');

        $params = array_merge(
            [
                'items' => $manager->getWidgetItemsData($widget, $this->getRequest()->query->get('_widgetId', null))
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
     * @param Dashboard $dashboard $dashboard
     * @param string    $permission
     * @return DashboardModel|null
     */
    protected function findAllowedDashboard(Dashboard $dashboard = null, $permission = 'VIEW')
    {
        if ($dashboard) {
            $dashboard = $this->getDashboardManager()->getDashboardModel($dashboard);
        } else {
            $dashboard = $this->getDashboardManager()->findUserActiveOrDefaultDashboard($this->getUser());
            if ($dashboard &&
                !$this->getSecurityFacade()->isGranted($permission, $dashboard->getEntity())
            ) {
                $dashboard = null;
            }
        }

        return $dashboard;
    }

    /**
     * @Route(
     *      "/grid/{widget}/{gridName}",
     *      name="oro_dashboard_grid",
     *      requirements={"gridName"="[\w\:-]+"}
     * )
     * @Template("OroDashboardBundle:Dashboard:grid.html.twig")
     *
     * @param string  $widget
     * @param string  $gridName
     * @param Request $request
     *
     * @return array
     */
    public function gridAction($widget, $gridName, Request $request)
    {
        $params       = $request->get('params', []);
        $renderParams = $request->get('renderParams', []);

        $viewId = $this->getWidgetConfigs()->getWidgetOptions()->get('gridView');
        if ($viewId && null !== $view = $this->findView($viewId)) {
            $params = array_merge(
                $params,
                [
                    ParameterBag::ADDITIONAL_PARAMETERS => [
                        GridViewsExtension::VIEWS_PARAM_KEY => $viewId
                    ],
                    '_filter'                           => $view->getFiltersData(),
                    '_sort_by'                          => $view->getSortersData(),
                ]
            );
        }

        $options = $this->getWidgetConfigs()->getWidgetOptions();
        $gridConfig = $this->getDatagridConfigurationProvider()->getConfiguration($gridName);
        if (isset($gridConfig['filters'], $gridConfig['filters']['columns'])) {
            if (!isset($params['_filter'])) {
                $params['_filter'] = [];
            }

            $filters = array_intersect_key($options->all(), $gridConfig['filters']['columns']);
            $params['_filter'] = array_merge($params['_filter'], $filters);
        }

        return array_merge(
            [
                'gridName'     => $gridName,
                'params'       => $params,
                'renderParams' => $renderParams,
            ],
            $this->getWidgetConfigs()->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @return ConfigurationProviderInterface
     */
    protected function getDatagridConfigurationProvider()
    {
        return $this->get('oro_datagrid.configuration.provider.chain');
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

    /**
     * @return WidgetConfigurationFormProvider
     */
    protected function getFormProvider()
    {
        return $this->get('oro_dashboard.provider.widget_configuration_form_provider');
    }

    /**
     * @return StateManager
     */
    protected function getStateManager()
    {
        return $this->get('oro_dashboard.manager.state');
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

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * @return SecurityFacade
     */
    protected function getSecurityFacade()
    {
        return $this->get('oro_security.security_facade');
    }
}
