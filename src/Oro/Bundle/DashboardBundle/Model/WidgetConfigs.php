<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Event\WidgetOptionsLoadEvent;
use Oro\Bundle\DashboardBundle\Model\StateManager;

use Oro\Component\Config\Resolver\ResolverInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WidgetConfigs
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var StateManager */
    protected $stateManager;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Request|null */
    protected $request;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param ConfigProvider          $configProvider
     * @param SecurityFacade          $securityFacade
     * @param ResolverInterface       $resolver
     * @param EntityManagerInterface  $entityManager
     * @param StateManager            $stateManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ConfigProvider $configProvider,
        SecurityFacade $securityFacade,
        ResolverInterface $resolver,
        EntityManagerInterface $entityManager,
        StateManager $stateManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->configProvider   = $configProvider;
        $this->securityFacade   = $securityFacade;
        $this->resolver         = $resolver;
        $this->entityManager    = $entityManager;
        $this->stateManager     = $stateManager;
        $this->dispatcher       = $dispatcher;
    }

    /**
     * Returns widget attributes with attribute name converted to use in widget's TWIG template
     *
     * @param string $widgetName The name of widget
     *
     * @return array
     */
    public function getWidgetAttributesForTwig($widgetName)
    {
        $result = [
            'widgetName' => $widgetName
        ];

        $widget = $this->configProvider->getWidgetConfig($widgetName);
        unset($widget['route']);
        unset($widget['route_parameters']);
        unset($widget['acl']);
        unset($widget['items']);

        foreach ($widget as $key => $val) {
            $attrName = 'widget';
            foreach (explode('_', str_replace('-', '_', $key)) as $keyPart) {
                $attrName .= ucfirst($keyPart);
            }
            $result[$attrName] = $val;
        }

        return $result;
    }

    /**
     * Returns filtered list of widget configuration
     * based on applicable flags and acl
     *
     * @return array
     */
    public function getWidgetConfigs()
    {
        return $this->filterWidgets($this->configProvider->getWidgetConfigs());
    }

    /**
     * Returns a list of items for the given widget
     *
     * @param string $widgetName The name of widget
     *
     * @return array
     */
    public function getWidgetItems($widgetName)
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);

        $items = isset($widgetConfig['items']) ? $widgetConfig['items'] : [];
        $items = $this->filterWidgets($items);

        return $items;
    }

    /**
     * Returns a list of options for current widget
     *
     * @return WidgetOptionBag
     */
    public function getCurrentWidgetOptions()
    {
        if (!$this->request) {
            return new WidgetOptionBag();
        }

        $widgetId = $this->request->query->get('_widgetId', null);
        if (!$widgetId) {
            return new WidgetOptionBag();
        }

        $widget = $this->findWidget($widgetId);
        $widgetState = $this->stateManager->getWidgetState($widget);

        $options = $widgetState->getOptions();
        if ($this->dispatcher->hasListeners(WidgetOptionsLoadEvent::EVENT_NAME)) {
            $event = new WidgetOptionsLoadEvent($options);
            $this->dispatcher->dispatch(WidgetOptionsLoadEvent::EVENT_NAME, $event);
            $options = $event->getOptions();
        }

        return new WidgetOptionBag($options);
    }

    /**
     * Filter widget configs based on acl enabled and applicable flag
     *
     * @param array $items
     *
     * @return array filtered items
     */
    protected function filterWidgets(array $items)
    {
        $securityFacade = $this->securityFacade;
        $resolver       = $this->resolver;

        return array_filter(
            $items,
            function (&$item) use ($securityFacade, $resolver) {
                $accessGranted = !isset($item['acl']) || $securityFacade->isGranted($item['acl']);
                $applicable    = true;
                $enabled = $item['enabled'];
                if (isset($item['applicable'])) {
                    $resolved   = $resolver->resolve([$item['applicable']]);
                    $applicable = reset($resolved);
                }

                unset ($item['acl'], $item['applicable'], $item['enabled']);

                return $enabled && $accessGranted && $applicable;
            }
        );
    }

    /**
     * @param int $id
     *
     * @return Widget
     */
    protected function findWidget($id)
    {
        return $this->entityManager->getRepository('OroDashboardBundle:Widget')->find($id);
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }
}
