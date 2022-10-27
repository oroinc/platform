<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Entity\Repository\DashboardRepository;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Form\Type\DashboardType;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Provider\WidgetConfigurationFormProvider;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for the Dashboard entity.
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
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
            'entity_class' => Dashboard::class
        ];
    }

    /**
     * @param Request $request
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
    public function viewAction(Request $request, Dashboard $dashboard = null)
    {
        $currentDashboard = $this->findAllowedDashboard($dashboard);

        if (!$currentDashboard) {
            return $this->quickLaunchpadAction();
        }

        if (!$this->isGranted('VIEW', $currentDashboard->getEntity())) {
            return $this->quickLaunchpadAction();
        }

        $changeActive = $request->get('change_dashboard', false);
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
                'widgets'    => $this->get(WidgetConfigs::class)->getWidgetConfigs()
            ]
        );
    }

    /**
     * @Route("/configure/{id}", name="oro_dashboard_configure", requirements={"id"="\d+"}, methods={"GET", "POST"})
     * @Template("@OroDashboard/Dashboard/dialog/configure.html.twig")
     *
     * @param Request $request
     * @param Widget $widget
     * @return array
     */
    public function configureAction(Request $request, Widget $widget)
    {
        if (!$this->isGranted('EDIT', $widget->getDashboard())) {
            throw new AccessDeniedException();
        }

        $form  = $this->get(WidgetConfigurationFormProvider::class)->getForm($widget->getName());
        $saved = false;

        $form->setData($this->get(WidgetConfigs::class)->getFormValues($widget));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $widget->setOptions($form->getData());
            $this->getDoctrine()->getManagerForClass(Widget::class)->flush();
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
     * @param Request $request
     * @param Dashboard $dashboard
     * @return array
     */
    public function updateAction(Request $request, Dashboard $dashboard)
    {
        $dashboardModel = $this->getDashboardManager()->getDashboardModel($dashboard);

        return $this->update($dashboardModel, $request);
    }

    /**
     * @Route("/create", name="oro_dashboard_create")
     * @Acl(
     *      id="oro_dashboard_create",
     *      type="entity",
     *      class="OroDashboardBundle:Dashboard",
     *      permission="CREATE"
     * )
     * @Template("@OroDashboard/Dashboard/update.html.twig")
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        $dashboardModel = $this->getDashboardManager()->createDashboardModel();

        return $this->update($dashboardModel, $request);
    }

    /**
     * @param DashboardModel $dashboardModel
     * @param Request $request
     * @return array
     */
    protected function update(DashboardModel $dashboardModel, Request $request)
    {
        $form = $this->createForm(
            DashboardType::class,
            $dashboardModel->getEntity(),
            [
                'create_new' => !$dashboardModel->getId()
            ]
        );

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->getDashboardManager()->save($dashboardModel, true);
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->get(TranslatorInterface::class)->trans('oro.dashboard.saved_message')
                );

                return $this->get(Router::class)->redirect($dashboardModel->getEntity());
            }
        }

        return ['entity' => $dashboardModel, 'form' => $form->createView()];
    }

    /**
     * @Route(
     *      "/widget/{widget}/{name}/{bundle}",
     *      name="oro_dashboard_widget",
     *      requirements={"widget"="[\w\-]+", "bundle"="^$|\w+", "name"="[\w\-]+"},
     *      defaults={"bundle"= ""}
     * )
     *
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    public function widgetAction($widget, $bundle, $name)
    {
        $view = !empty($bundle)
            ? sprintf('@%s/Dashboard/%s.html.twig', $bundle, $name)
            : sprintf('Dashboard/%s.html.twig', $name);

        return $this->render(
            $view,
            $this->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @Route(
     *      "/itemized_widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_itemized_widget",
     *      requirements={"widget"="[\w\-]+", "bundle"="\w+", "name"="[\w\-]+"}
     * )
     *
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    public function itemizedWidgetAction($widget, $bundle, $name)
    {
        /** @var WidgetConfigs $manager */
        $manager = $this->get(WidgetConfigs::class);

        $params = array_merge(
            [
                'items' => $manager->getWidgetItems($widget)
            ],
            $manager->getWidgetAttributesForTwig($widget)
        );

        return $this->render(
            sprintf('@%s/Dashboard/%s.html.twig', $bundle, $name),
            $params
        );
    }

    /**
     * @Route(
     *      "/itemized_data_widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_itemized_data_widget",
     *      requirements={"widget"="[\w\-]+", "bundle"="\w+", "name"="[\w\-]+"}
     * )
     * @param Request $request
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    public function itemizedDataWidgetAction(Request $request, $widget, $bundle, $name)
    {
        /** @var WidgetConfigs $manager */
        $manager = $this->get(WidgetConfigs::class);

        $params = array_merge(
            [
                'items' => $manager->getWidgetItemsData($widget, $request->query->get('_widgetId', null))
            ],
            $manager->getWidgetAttributesForTwig($widget)
        );

        return $this->render(
            sprintf('@%s/Dashboard/%s.html.twig', $bundle, $name),
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
            '@OroDashboard/Index/quickLaunchpad.html.twig',
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
            if ($dashboard && !$this->isGranted($permission, $dashboard->getEntity())) {
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
     * @Template("@OroDashboard/Dashboard/grid.html.twig")
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

        $viewId = $this->get(WidgetConfigs::class)->getWidgetOptions()->get('gridView');
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

        $options = $this->get(WidgetConfigs::class)->getWidgetOptions();
        $gridConfig = $this->get(ConfigurationProviderInterface::class)->getConfiguration($gridName);
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
            $this->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @param int $id
     *
     * @return GridView
     */
    protected function findView($id)
    {
        return $this->getDoctrine()->getRepository(GridView::class)->find($id);
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->get(Manager::class);
    }

    /**
     * @return DashboardRepository
     */
    protected function getDashboardRepository()
    {
        return $this->getDoctrine()->getRepository(Dashboard::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            WidgetConfigs::class,
            TranslatorInterface::class,
            Router::class,
            WidgetConfigurationFormProvider::class,
            Manager::class,
            ConfigurationProviderInterface::class
        ]);
    }
}
