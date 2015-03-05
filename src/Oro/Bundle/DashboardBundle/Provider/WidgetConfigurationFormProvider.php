<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class WidgetConfigurationFormProvider
{
    /** @var array */
    protected $config;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(ConfigProvider $configProvider, FormFactoryInterface $formFactory)
    {
        $this->configProvider = $configProvider;
        $this->formFactory = $formFactory;
    }

    /**
     * @param string $widget
     *
     * @return bool
     */
    public function hasForm($widget)
    {
        $widgetConfig = $this->configProvider->getWidgetConfig($widget);

        return isset($widgetConfig['fields']);
    }

    /**
     * @param string $widget
     *
     * @return FormInterface
     * @throws InvalidArgumentException
     */
    public function getForm($widget)
    {
        if (!$this->hasForm($widget)) {
            throw new InvalidArgumentException(sprintf('Can\'t find form for widget "%s"', $widget));
        }

        $widgetConfig = $this->configProvider->getWidgetConfig($widget);
        $fields = $widgetConfig['fields'];

        $builder = $this->formFactory->createNamedBuilder($widget);
        foreach ($fields as $name => $config) {
            $config['options'] = array();
            $builder->add($name, $config['type'], $config['options']);
        }

        return $builder->getForm();
    }
}
