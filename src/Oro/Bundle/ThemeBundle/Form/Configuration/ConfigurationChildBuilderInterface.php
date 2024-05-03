<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * OptionFormTypeBuilderInterface interface for theme configuration option builder(s)
 *
 * Services that will implement this interface will extend the available FormType(s) for the theme configuration section
 */
interface ConfigurationChildBuilderInterface
{
    public const VIEW_MODULE_NAME = 'orotheme/js/app/views/theme-configuration-preview-view';

    /**
     * Reserved preview key that is used for default preview and for options when they don't have images.
     */
    public const DEFAULT_PREVIEW_KEY = '_default';

    public const DATA_ROLE_CHANGE_PREVIEW = 'change-preview';

    public static function getType(): string;

    /**
     * Checks if the option type is supported by the builder.
     */
    public function supports(array $option): bool;

    /**
     * Adds an input of the appropriate type to the form, if supported.
     */
    public function buildOption(FormBuilderInterface $builder, array $option): void;

    /**
     * Adds the possibility to modify the option form view.
     */
    public function finishView(FormView $view, FormInterface $form, array $formOptions, array $themeOption): void;
}
