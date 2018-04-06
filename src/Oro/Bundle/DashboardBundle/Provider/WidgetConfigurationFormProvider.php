<?php

namespace Oro\Bundle\DashboardBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\Tree\FieldNodeDefinition;
use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\DashboardBundle\Model\ConfigProvider;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class WidgetConfigurationFormProvider
{
    const FORM_FIELDS_KEY = 'configuration';

    /** @var array */
    protected $config;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider       $configProvider
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        ConfigProvider $configProvider,
        FormFactoryInterface $formFactory
    ) {
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

        return isset($widgetConfig[static::FORM_FIELDS_KEY]);
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
        $fields = $widgetConfig[static::FORM_FIELDS_KEY];

        $builder = $this->formFactory->createNamedBuilder($widget);
        foreach ($fields as $name => $config) {
            $field = new FieldNodeDefinition($name, $config);
            $builder->add($field->getName(), $config['type'], $field->getOptions());
        }

        return $builder->getForm();
    }
}
