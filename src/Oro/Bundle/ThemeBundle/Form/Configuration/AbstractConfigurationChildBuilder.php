<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * Provide a general functionality for FormType from the theme configuration section of theme.yml files
 */
abstract class AbstractConfigurationChildBuilder implements ConfigurationChildBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    abstract public function supports(array $option): bool;

    /**
     * Returns the FormType class name that will be used for this option
     */
    abstract protected function getTypeClass(): string;

    /**
     * Returns default options for FormType class that will be used for this option
     */
    abstract protected function getDefaultOptions(): array;

    /**
     * {@inheritDoc}
     */
    public function buildOption(FormBuilderInterface $builder, array $option): void
    {
        $builder->add(
            $option['name'],
            $this->getTypeClass(),
            array_merge($this->getDefaultOptions(), $this->getConfiguredOptions($option))
        );
    }

    /**
     * Returns options for FormType class that was configured for this option
     */
    protected function getConfiguredOptions(array $option): array
    {
        return [
            'label' => $option['label'],
            'empty_data' => $option['default'],
            'attr' => array_merge($this->getPreviewAttributes($option), $option['attributes'] ?? []),
            ...$option['options'] ?? []
        ];
    }

    /**
     * Adds to the FormType attributes that are required for the correct display of the preview on the back-office UI
     */
    protected function getPreviewAttributes(array $option): array
    {
        $attr = [];
        if (array_key_exists('previews', $option) && !empty($option['previews'])) {
            $attr['data-role'] = 'change-preview';
            $attr['data-preview-key'] = $option['name'];
            $attr['data-preview-default'] = $option['default'];
            foreach ($option['previews'] as $value => $preview) {
                $attr["data-preview-$value"] = $preview;
            }
        }

        return $attr;
    }
}
