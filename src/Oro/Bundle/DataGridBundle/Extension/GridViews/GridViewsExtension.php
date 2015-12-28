<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Event\GridViewsLoadEvent;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class GridViewsExtension extends AbstractExtension
{
    const GRID_VIEW_ROOT_PARAM = '_grid_view';
    const DISABLED_PARAM       = '_disabled';

    const VIEWS_LIST_KEY           = 'views_list';
    const VIEWS_PARAM_KEY          = 'view';
    const MINIFIED_VIEWS_PARAM_KEY = 'v';
    const DEFAULT_VIEW_ID = '__all__';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ManagerRegistry */
    protected $registry;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param SecurityFacade           $securityFacade
     * @param TranslatorInterface      $translator
     * @param ManagerRegistry          $registry
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        ManagerRegistry $registry
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade  = $securityFacade;
        $this->translator      = $translator;
        $this->registry        = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return !$this->isDisabled();
    }

    /**
     * @return bool
     */
    protected function isDisabled()
    {
        $parameters = $this->getParameters()->get(self::GRID_VIEW_ROOT_PARAM, []);

        return !empty($parameters[self::DISABLED_PARAM]);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $defaultViewId = $this->getDefaultViewId($config->getName());
        $params        = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

        if (isset($params[self::VIEWS_PARAM_KEY])) {
            $currentView = (int)$params[self::VIEWS_PARAM_KEY];
        } else {
            $currentView                   = $defaultViewId;
            $params[self::VIEWS_PARAM_KEY] = $defaultViewId;
            $this->getParameters()->set(ParameterBag::ADDITIONAL_PARAMETERS, $params);
        }

        $data->offsetAddToArray('initialState', ['gridView' => $defaultViewId]);
        $data->offsetAddToArray('state', ['gridView' => $currentView]);

        $allLabel = null;
        if (isset($config['options'])
                &&isset($config['options']['gridViews'])
                && isset($config['options']['gridViews']['allLabel'])
            ) {
            $allLabel = $this->translator->trans($config['options']['gridViews']['allLabel']);
        }

        /** @var AbstractViewsList $list */
        $list          = $config->offsetGetOr(self::VIEWS_LIST_KEY, false);
        $systemAllView = new View(self::DEFAULT_VIEW_ID);
        $gridViews     = [
            'choices' => [
                [
                    'label' => $allLabel,
                    'value' => self::DEFAULT_VIEW_ID,
                ],
            ],
            'views' => [],
        ];
        if ($list !== false) {
            $configuredGridViews = $list->getMetadata();
            $configuredGridViews['views'] = array_merge($gridViews['views'], $configuredGridViews['views']);
            $configuredGridViews['choices'] = array_merge($gridViews['choices'], $configuredGridViews['choices']);
            $gridViews = $configuredGridViews;
        }

        if ($this->eventDispatcher->hasListeners(GridViewsLoadEvent::EVENT_NAME)) {
            $event = new GridViewsLoadEvent($config->getName(), $gridViews);
            $this->eventDispatcher->dispatch(GridViewsLoadEvent::EVENT_NAME, $event);
            $gridViews = $event->getGridViews();
        }
        $hasDefault = false;
        foreach ($gridViews['views'] as $view) {
            if (!empty($view['is_default'])) {
                $hasDefault = true;
                break;
            }
        }

        $systemAllView->setDefault(!$hasDefault);
        $gridViews['gridName'] = $config->getName();
        $gridViews['permissions'] = $this->getPermissions();
        $gridViews['views'][] = $systemAllView->getMetadata();
        $data->offsetAddToArray('gridViews', $gridViews);
    }

    /**
     * @param string $gridName
     *
     * @return int|string
     */
    protected function getDefaultViewId($gridName)
    {
        $defaultGridView = null;
        if ($this->securityFacade->isGranted('oro_datagrid_gridview_view')) {
            $repository      = $this->registry->getRepository('OroDataGridBundle:GridView');
            $defaultGridView = $repository->findDefaultGridView($gridName, $this->securityFacade->getLoggedUser());
        }

        return $defaultGridView ? $defaultGridView->getId() : self::DEFAULT_VIEW_ID;
    }

    /**
     * @return array
     */
    private function getPermissions()
    {
        return [
            'VIEW' => $this->securityFacade->isGranted('oro_datagrid_gridview_view'),
            'CREATE' => $this->securityFacade->isGranted('oro_datagrid_gridview_create'),
            'EDIT' => $this->securityFacade->isGranted('oro_datagrid_gridview_update'),
            'DELETE' => $this->securityFacade->isGranted('oro_datagrid_gridview_delete'),
            'SHARE' => $this->securityFacade->isGranted('oro_datagrid_gridview_publish'),
            'EDIT_SHARED' => $this->securityFacade->isGranted('oro_datagrid_gridview_update_public'),
        ];
    }

    /**
     * @param ParameterBag $parameters
     */
    public function setParameters(ParameterBag $parameters)
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $additional = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

            if (array_key_exists(self::MINIFIED_VIEWS_PARAM_KEY, $minifiedParameters)) {
                $additional[self::VIEWS_PARAM_KEY] = $minifiedParameters[self::MINIFIED_VIEWS_PARAM_KEY];
            }

            $parameters->set(ParameterBag::ADDITIONAL_PARAMETERS, $additional);
        }

        parent::setParameters($parameters);
    }
}
