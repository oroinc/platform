<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Entity\Widget;
use Oro\Bundle\DashboardBundle\Event\WidgetItemsLoadDataEvent;
use Oro\Bundle\DashboardBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\DashboardBundle\Filter\WidgetConfigVisibilityFilter;
use Oro\Bundle\DashboardBundle\Provider\ConfigValueProvider;
use Oro\Component\Config\Resolver\ResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The dashboard widget configuration model.
 */
class WidgetConfigs
{
    protected array $widgetOptionsById = [];

    public function __construct(
        protected ConfigProvider $configProvider,
        protected ResolverInterface $resolver,
        protected ManagerRegistry $doctrine,
        protected ConfigValueProvider $valueProvider,
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $eventDispatcher,
        protected WidgetConfigVisibilityFilter $visibilityFilter,
        protected RequestStack $requestStack
    ) {
    }

    /**
     * Returns widget attributes with attribute name converted to use in widget's TWIG template.
     */
    public function getWidgetAttributesForTwig(string $widgetName): array
    {
        $result = [
            'widgetName' => $widgetName
        ];

        $widget = $this->configProvider->getWidgetConfig($widgetName);
        if (isset($widget['data_items'])) {
            $widget['data_items'] = $this->visibilityFilter->filterConfigs($widget['data_items'], $widgetName);
        }
        unset($widget['route'], $widget['route_parameters'], $widget['acl'], $widget['items']);

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

        $request = $this->requestStack->getCurrentRequest();
        // get grid params, if exists, this line is required
        // to not override params passed via request to controller
        $gridParams = $request ? $request->get('params', []) : [];
        if (!empty($gridParams)) {
            $result['params'] = $this->addGridConfigParams($gridParams);
        }

        return $result;
    }

    /**
     * Adds ability to pass widget instance configuration params as grid params
     * so they could be used to bound them to Query parameters,
     * which allows to filter grid by widget configuration values.
     */
    protected function addGridConfigParams(array $gridParams): array
    {
        $result = [];
        $widgetOptions = $this->getWidgetOptions();

        // check if there are some parameter that marked as 'use_config_value'
        foreach ($gridParams as $paramName => $paramValue) {
            if ('use_config_value' !== $paramValue) {
                continue;
            }

            // replace config param value (raw value) in grid parameters
            $result[$paramName] = $widgetOptions->get($paramName);
        }

        return $result;
    }

    /**
     * Returns filtered list of widget configuration
     * based on applicable flags and ACL.
     */
    public function getWidgetConfigs(): array
    {
        return $this->visibilityFilter->filterConfigs($this->configProvider->getWidgetConfigs());
    }

    /**
     * Returns widget configuration or null based on applicable flags and ACL.
     */
    public function getWidgetConfig(string $widgetName): ?array
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName, false);
        if (null === $widgetConfig) {
            return null;
        }

        $configs = $this->visibilityFilter->filterConfigs([$widgetName => $widgetConfig]);
        $config = reset($configs);

        return $config ?: null;
    }

    /**
     * Returns a list of items for the given widget.
     *
     * @throws InvalidConfigurationException if the widget config was not found
     */
    public function getWidgetItems(string $widgetName): array
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);

        return $this->visibilityFilter->filterConfigs($widgetConfig['items'] ?? [], $widgetName);
    }

    /**
     * Returns items' data for the given widget.
     */
    public function getWidgetItemsData(string $widgetName, ?int $widgetId): array
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widgetName);
        $widgetOptions = $this->getWidgetOptions($widgetId);

        $items = $this->visibilityFilter->filterConfigs($widgetConfig['data_items'] ?? [], $widgetName);

        if ($this->eventDispatcher->hasListeners(WidgetItemsLoadDataEvent::EVENT_NAME)) {
            $event = new WidgetItemsLoadDataEvent($items, $widgetConfig, $widgetOptions);
            $this->eventDispatcher->dispatch($event, WidgetItemsLoadDataEvent::EVENT_NAME);
            $items = $event->getItems();
        }

        foreach ($items as $itemName => $config) {
            $resolvedData = $this->resolver->resolve(
                [$config['data_provider']],
                ['widgetOptions' => $widgetOptions]
            );
            $items[$itemName]['value'] = $resolvedData[0];
        }

        return $items;
    }

    /**
     * Returns a list of options for the given widget or for the current widget if the widget ID is not specified.
     *
     * @throws InvalidConfigurationException if the widget config was not found
     */
    public function getWidgetOptions(?int $widgetId = null): WidgetOptionBag
    {
        if (null === $widgetId) {
            $request = $this->requestStack->getCurrentRequest();
            if (null !== $request) {
                $widgetIdFromRequest = $request->query->get('_widgetId');
                if ($widgetIdFromRequest) {
                    $widgetId = (int)$widgetIdFromRequest;
                }
            }
        }

        if (!$widgetId) {
            return new WidgetOptionBag();
        }

        if (!empty($this->widgetOptionsById[$widgetId])) {
            return new WidgetOptionBag($this->widgetOptionsById[$widgetId]);
        }

        $widget = $this->findWidget($widgetId);
        if (!$widget) {
            return new WidgetOptionBag();
        }

        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());
        $options = $widget->getOptions();

        foreach ($widgetConfig['configuration'] as $name => $config) {
            $options[$name] = $this->valueProvider->getConvertedValue(
                $widgetConfig,
                $config['type'],
                $options[$name] ?? null,
                $config,
                $options
            );
        }

        $this->widgetOptionsById[$widgetId] = $options;

        return new WidgetOptionBag($options);
    }

    /**
     * Returns form values for the given widget.
     */
    public function getFormValues(Widget $widget): array
    {
        $options = $widget->getOptions();
        $widgetConfig = $this->configProvider->getWidgetConfig($widget->getName());

        foreach ($widgetConfig['configuration'] as $name => $config) {
            $options[$name] = $this->valueProvider->getFormValue($config['type'], $config, $options[$name] ?? null);
        }

        return $this->loadDefaultValue($options, $widgetConfig);
    }

    protected function loadDefaultValue(array $options, array $widgetConfig): array
    {
        if (!isset($options['title']) || !$options['title']['title'] || $options['title']['useDefault']) {
            $options['title']['title'] = isset($widgetConfig['label'])
                ? $this->translator->trans((string) $widgetConfig['label'])
                : '';
            $options['title']['useDefault'] = true;
        }

        return $options;
    }

    protected function findWidget(int $widgetId): ?Widget
    {
        return $this->doctrine->getRepository(Widget::class)->find($widgetId);
    }
}
