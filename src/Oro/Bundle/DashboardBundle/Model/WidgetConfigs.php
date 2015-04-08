<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use Oro\Component\Config\Resolver\ResolverInterface;

class WidgetConfigs
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ResolverInterface */
    protected $resolver;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Request|null */
    protected $request;

    /** @var ConfigValueProvider */
    protected $valueProvider;

    /**
     * @param ConfigProvider         $configProvider
     * @param SecurityFacade         $securityFacade
     * @param ResolverInterface      $resolver
     * @param EntityManagerInterface $entityManager
     * @param ConfigValueProvider    $valueProvider
     */
    public function __construct(
        ConfigProvider $configProvider,
        SecurityFacade $securityFacade,
        ResolverInterface $resolver,
        EntityManagerInterface $entityManager,
        ConfigValueProvider $valueProvider
    ) {
        $this->configProvider = $configProvider;
        $this->securityFacade = $securityFacade;
        $this->resolver       = $resolver;
        $this->entityManager  = $entityManager;
        $this->valueProvider  = $valueProvider;
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

        $options = $widget['configuration'];
        foreach ($options as $name => $config) {
            $widget['configuration'][$name]['value'] = $this->valueProvider->getViewValue(
                $config['type'],
                $this->getWidgetOptions()->get($name)
            );
        }

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
     * Returns a list of options for widget with id $widgetId or current widget if $widgetId is not specified
     *
     * @param int|null $widgetId
     *
     * @return WidgetOptionBag
     */
    public function getWidgetOptions($widgetId = null)
    {
        if (!$this->request) {
            return new WidgetOptionBag();
        }

        if (!$widgetId) {
            $widgetId = $this->request->query->get('_widgetId', null);
        }

        if (!$widgetId) {
            return new WidgetOptionBag();
        }

        $widget       = $this->findWidget($widgetId);
        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());
        $options      = $widget->getOptions();

        foreach ($widgetConfig['configuration'] as $name => $config) {
            $value          = isset($options[$name]) ? $options[$name] : null;
            $options[$name] = $this->valueProvider->getConvertedValue($widgetConfig, $config['type'], $value);
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
                $enabled       = $item['enabled'];
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
