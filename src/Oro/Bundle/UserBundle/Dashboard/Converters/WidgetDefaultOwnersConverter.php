<?php

namespace Oro\Bundle\UserBundle\Dashboard\Converters;

use Oro\Bundle\DashboardBundle\Provider\ConfigValueConverterAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The dashboard widget configuration converter for select "owners",
 * like a user, a business unit, and a role.
 */
class WidgetDefaultOwnersConverter extends ConfigValueConverterAbstract
{
    /** @var array [field name => ['converter' => ConfigValueConverterAbstract, 'label' => string], ...] */
    protected array $converters = [];

    public function __construct(
        protected TranslatorInterface $translator
    ) {
    }

    public function setConverter(ConfigValueConverterAbstract $converter, string $field, string $label): void
    {
        $this->converters[$field] = ['converter' => $converter, 'label' => $label];
    }

    #[\Override]
    public function getViewValue(mixed $value): mixed
    {
        $data = [];
        if ($value && \is_array($value)) {
            foreach ($value as $field => $ids) {
                if (isset($this->converters[$field]) && $ids) {
                    $ids = array_filter($ids);
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

    #[\Override]
    public function getConvertedValue(
        array $widgetConfig,
        mixed $value = null,
        array $config = [],
        array $options = []
    ): mixed {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getConvertedValue($widgetConfig, $value, $config, $options);
    }

    #[\Override]
    public function getFormValue(array $config, mixed $value): mixed
    {
        if (null === $value) {
            return $this->getDefaultChoices($config);
        }

        return parent::getFormValue($config, $value);
    }

    protected function getDefaultChoices(array $config): mixed
    {
        return $config['converter_attributes']['default_selected'] ?? [];
    }
}
