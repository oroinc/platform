<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

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

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param SecurityFacade $securityFacade
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $params  = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
        if (isset($params[self::VIEWS_PARAM_KEY])) {
            $currentView = (int)$params[self::VIEWS_PARAM_KEY];
        } else {
            $currentView = self::DEFAULT_VIEW_ID;
        }

        $data->offsetAddToArray('initialState', ['gridView' => self::DEFAULT_VIEW_ID]);
        $data->offsetAddToArray('state', ['gridView' => $currentView]);

        $allLabel = null;
        if (isset($config['options'])
                &&isset($config['options']['gridViews'])
                && isset($config['options']['gridViews']['allLabel'])
            ) {
            $allLabel = $this->translator->trans($config['options']['gridViews']['allLabel']);
        }

        /** @var AbstractViewsList $list */
        $list = $config->offsetGetOr(self::VIEWS_LIST_KEY, false);
        $gridViews = [
            'choices' => [
                [
                    'label' => $allLabel,
                    'value' => self::DEFAULT_VIEW_ID,
                ],
            ],
            'views' => [
                (new View(self::DEFAULT_VIEW_ID))->getMetadata(),
            ],
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

        $gridViews['gridName'] = $config->getName();
        $gridViews['permissions'] = $this->getPermissions();
        $data->offsetSet('gridViews', $gridViews);
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
