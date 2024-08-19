<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Provide a general functionality for FormType from the theme configuration section of theme.yml files
 */
abstract class AbstractConfigurationChildBuilder implements ConfigurationChildBuilderInterface
{
    public function __construct(
        protected Packages $packages
    ) {
    }

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
    public function supports(array $option): bool
    {
        return $option['type'] === static::getType();
    }

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
        $builder
            ->get($option['name'])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($option) {
                $parentData = $event->getForm()->getParent()?->getData() ?? [];

                if (\array_key_exists('default', $option) && !\array_key_exists($option['name'], $parentData)) {
                    $event->setData($option['default']);
                }
            });
    }

    public function finishView(FormView $view, FormInterface $form, array $formOptions, array $themeOption): void
    {
        if ($this->isApplicablePreviews($themeOption)) {
            $defaultPreview = $this->getOptionPreview($themeOption, static::DEFAULT_PREVIEW_KEY);
            if ($defaultPreview) {
                $view->vars['attr']['data-default-preview'] = $defaultPreview;
            }

            $preview = $this->getOptionPreview($themeOption, $form->getData(), true);
            if ($preview) {
                $view->vars['attr']['data-preview'] = $preview;
            }

            $view->vars['group_attr'] = [
                'data-page-component-view' => static::VIEW_MODULE_NAME,
                'data-page-component-options' => [
                    'autoRender' => true,
                    'previewSource' => $preview ?? '',
                    'defaultPreview' => $defaultPreview ?? '',
                ]
            ];
        }
    }

    /**
     * Returns options for FormType class that was configured for this option
     */
    protected function getConfiguredOptions(array $option): array
    {
        return [
            'label' => $option['label'],
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
        if ($this->isApplicablePreviews($option)) {
            $attr['data-role'] = static::DATA_ROLE_CHANGE_PREVIEW;
            $attr['data-preview-key'] = $option['name'];
        }

        return $attr;
    }

    protected function getOptionPreview(array $option, mixed $value = null, bool $default = false): ?string
    {
        $key = $value ?? $option['default'] ?? null;
        $preview = $option['previews'][$key] ?? null;

        if (!$preview && $default) {
            $preview = $option['previews'][static::DEFAULT_PREVIEW_KEY] ?? null;
        }

        return $preview ? $this->packages->getUrl($preview) : $preview;
    }

    protected function isApplicablePreviews(array $option): bool
    {
        return array_key_exists('previews', $option) && !empty($option['previews']);
    }
}
