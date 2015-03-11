<?php

namespace Oro\Bundle\DataGridBundle\Extension\GridViews;

use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param SecurityFacade $securityFacade
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, SecurityFacade $securityFacade)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $list = $config->offsetGetOr(self::VIEWS_LIST_KEY, false);

        if ($list !== false && !$list instanceof AbstractViewsList) {
            throw new InvalidTypeException(
                sprintf(
                    'Invalid type for path "%s.%s". Expected AbstractViewsList, but got %s.',
                    $config->getName(),
                    self::VIEWS_LIST_KEY,
                    gettype($list)
                )
            );
        }

        return $list !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $params      = $this->getParameters()->get(ParameterBag::ADDITIONAL_PARAMETERS, []);
        $currentView = isset($params[self::VIEWS_PARAM_KEY]) ? $params[self::VIEWS_PARAM_KEY] : null;
        $data->offsetAddToArray('initialState', ['gridView' => null]);
        $data->offsetAddToArray('state', ['gridView' => $currentView]);

        /** @var AbstractViewsList $list */
        $list = $config->offsetGetOr(self::VIEWS_LIST_KEY, false);
        $gridViews = [];
        if ($list !== false) {
            $gridViews = $list->getMetadata();
        }

        if ($this->eventDispatcher->hasListeners(GridViewsLoadEvent::EVENT_NAME)) {
            $event = new GridViewsLoadEvent($config->getName(), $gridViews);
            $this->eventDispatcher->dispatch(GridViewsLoadEvent::EVENT_NAME, $event);
            $gridViews = $event->getGridViews();
        }

        if ($gridViews) {
            $gridViews['permissions'] = $this->getPermissions();
            $data->offsetSet('gridViews', $gridViews);
        }
    }

    /**
     * @return array
     */
    private function getPermissions()
    {
        return [
            'CREATE' => $this->securityFacade->isGranted('oro_datagrid_gridview_create'),
            'EDIT' => $this->securityFacade->isGranted('oro_datagrid_gridview_update'),
            'DELETE' => $this->securityFacade->isGranted('oro_datagrid_gridview_delete'),
            'SHARE' => $this->securityFacade->isGranted('oro_datagrid_gridview_create_public'),
            'EDIT_SHARED' => $this->securityFacade->isGranted('oro_datagrid_gridview_edit_public'),
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
