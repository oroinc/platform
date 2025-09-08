<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
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
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
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
 */
#[Route(path: '/dashboard')]
class DashboardController extends AbstractController
{
    #[Route(
        path: '.{_format}',
        name: 'oro_dashboard_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template]
    #[Acl(id: 'oro_dashboard_view', type: 'entity', class: Dashboard::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [
            'entity_class' => Dashboard::class
        ];
    }

    /**
     * @param Request $request
     * @param Dashboard|null $dashboard
     *
     * @return Response
     */
    #[Route(path: '/view/{id}', name: 'oro_dashboard_view', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    public function viewAction(Request $request, ?Dashboard $dashboard = null)
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
                'widgets'    => $this->container->get(WidgetConfigs::class)->getWidgetConfigs()
            ]
        );
    }

    /**
     *
     * @param Request $request
     * @param Widget $widget
     * @return array
     */
    #[Route(
        path: '/configure/{id}',
        name: 'oro_dashboard_configure',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    #[Template('@OroDashboard/Dashboard/dialog/configure.html.twig')]
    public function configureAction(Request $request, Widget $widget)
    {
        if (!$this->isGranted('EDIT', $widget->getDashboard())) {
            throw new AccessDeniedException();
        }

        $form  = $this->container->get(WidgetConfigurationFormProvider::class)->getForm($widget->getName());
        $saved = false;

        $form->setData($this->container->get(WidgetConfigs::class)->getFormValues($widget));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $widget->setOptions($form->getData());
            $this->container->get('doctrine')->getManagerForClass(Widget::class)->flush();
            $saved = true;
        }

        return [
            'form'       => $form->createView(),
            'formAction' => $request->getRequestUri(),
            'saved'      => $saved
        ];
    }

    /**
     *
     * @param Request $request
     * @param Dashboard $dashboard
     * @return array
     */
    #[Route(path: '/update/{id}', name: 'oro_dashboard_update', requirements: ['id' => '\d+'], defaults: ['id' => 0])]
    #[Template]
    #[Acl(id: 'oro_dashboard_update', type: 'entity', class: Dashboard::class, permission: 'EDIT')]
    public function updateAction(Request $request, Dashboard $dashboard)
    {
        $dashboardModel = $this->getDashboardManager()->getDashboardModel($dashboard);

        return $this->update($dashboardModel, $request);
    }

    /**
     * @param Request $request
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_dashboard_create')]
    #[Template('@OroDashboard/Dashboard/update.html.twig')]
    #[Acl(id: 'oro_dashboard_create', type: 'entity', class: Dashboard::class, permission: 'CREATE')]
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
                    $this->container->get(TranslatorInterface::class)->trans('oro.dashboard.saved_message')
                );

                return $this->container->get(Router::class)->redirect($dashboardModel->getEntity());
            }
        }

        return ['entity' => $dashboardModel, 'form' => $form->createView()];
    }

    /**
     *
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    #[Route(
        path: '/widget/{widget}/{name}/{bundle}',
        name: 'oro_dashboard_widget',
        requirements: ['widget' => '[\w\-]+', 'bundle' => '^$|\w+', 'name' => '[\w\-]+'],
        defaults: ['bundle' => '']
    )]
    public function widgetAction($widget, $bundle, $name)
    {
        $view = !empty($bundle)
            ? sprintf('@%s/Dashboard/%s.html.twig', $bundle, $name)
            : sprintf('Dashboard/%s.html.twig', $name);

        return $this->render(
            $view,
            $this->container->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     *
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    #[Route(
        path: '/itemized_widget/{widget}/{bundle}/{name}',
        name: 'oro_dashboard_itemized_widget',
        requirements: ['widget' => '[\w\-]+', 'bundle' => '\w+', 'name' => '[\w\-]+']
    )]
    public function itemizedWidgetAction($widget, $bundle, $name)
    {
        /** @var WidgetConfigs $manager */
        $manager = $this->container->get(WidgetConfigs::class);

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
     * @param Request $request
     * @param string $widget
     * @param string $bundle
     * @param string $name
     * @return Response
     */
    #[Route(
        path: '/itemized_data_widget/{widget}/{bundle}/{name}',
        name: 'oro_dashboard_itemized_data_widget',
        requirements: ['widget' => '[\w\-]+', 'bundle' => '\w+', 'name' => '[\w\-]+']
    )]
    public function itemizedDataWidgetAction(Request $request, $widget, $bundle, $name)
    {
        /** @var WidgetConfigs $manager */
        $manager = $this->container->get(WidgetConfigs::class);

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

    #[Route(path: '/launchpad', name: 'oro_dashboard_quick_launchpad')]
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
     * @param Dashboard|null $dashboard $dashboard
     * @param string    $permission
     * @return DashboardModel|null
     */
    protected function findAllowedDashboard(?Dashboard $dashboard = null, $permission = 'VIEW')
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
     *
     * @param string  $widget
     * @param string  $gridName
     * @param Request $request
     *
     * @return array
     */
    #[Route(path: '/grid/{widget}/{gridName}', name: 'oro_dashboard_grid', requirements: ['gridName' => '[\w\:-]+'])]
    #[Template('@OroDashboard/Dashboard/grid.html.twig')]
    public function gridAction($widget, $gridName, Request $request)
    {
        $params       = $request->get('params', []);
        $renderParams = $request->get('renderParams', []);

        $viewId = $this->container->get(WidgetConfigs::class)->getWidgetOptions()->get('gridView');
        if ($viewId && null !== $view = $this->findView($gridName, $viewId)) {
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

        $options = $this->container->get(WidgetConfigs::class)->getWidgetOptions();
        $gridConfig = $this->container->get(ConfigurationProviderInterface::class)->getConfiguration($gridName);
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
            $this->container->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @param string $gridName
     * @param mixed $id
     *
     * @return GridView
     */
    protected function findView(string $gridName, $id)
    {
        return $this->container->get(GridViewManager::class)->getView($id, true, $gridName);
    }

    /**
     * @return Manager
     */
    protected function getDashboardManager()
    {
        return $this->container->get(Manager::class);
    }

    /**
     * @return DashboardRepository
     */
    protected function getDashboardRepository()
    {
        return $this->container->get('doctrine')->getRepository(Dashboard::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            WidgetConfigs::class,
            TranslatorInterface::class,
            Router::class,
            WidgetConfigurationFormProvider::class,
            Manager::class,
            ConfigurationProviderInterface::class,
            'doctrine' => ManagerRegistry::class,
            GridViewManager::class
        ]);
    }
}
