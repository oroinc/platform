<?php

namespace Oro\Bundle\UserBundle\Dashboard\Converters;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;

class WidgetDefaultOwnersConverter extends ConfigValueConverterAbstract
{
    /** @var TranslatorInterface */
    protected $translator;

    protected $converters = [];

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ConfigValueConverterAbstract $converter
     * @param string                       $field
     * @param string                       $label
     */
    public function setConverter(ConfigValueConverterAbstract $converter, $field, $label)
    {
        $this->converters[$field] = [
            'converter' => $converter,
            'label'     => $label
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewValue($value)
    {
        $data = [];
        if ($value && is_array($value)) {
            foreach ($value as $field => $ids) {
                $ids = array_filter($ids);
                if (isset($this->converters[$field]) && $ids) {
                    /** @var ConfigValueConverterAbstract $converter */
                    $converter = $this->converters[$field]['converter'];
                    $viewValue = $converter->getViewValue($ids);
                    if ($viewValue) {
                        $data[$this->converters[$field]['label']] = $converter->getViewValue($ids);
                    }
                }
            }
        }

        if ($data) {
            return $data;
        }

        return $this->translator->trans('oro.user.dashboard.all_owners');
    }

    /**
     * {@inheritdoc}
     */
    public function getConvertedValue(array $widgetConfig, $value = null, array $config = [], array $options = [])
    {
        if ($value === null) {
            return $this->getDefaultChoices($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormValue(array $config, $value)
    {
        if ($value === null) {
            return $this->getDefaultChoices($config);
        }

        return parent::getFormValue($config, $value);
    }

    /**
     * @param array $config
     *
     * @return array
     */
    protected function getDefaultChoices(array $config)
    {
        $values = [];

        if (isset($config['converter_attributes']['default_selected'])) {
            $values = $config['converter_attributes']['default_selected'];
        }

        return $values;
    }
}
