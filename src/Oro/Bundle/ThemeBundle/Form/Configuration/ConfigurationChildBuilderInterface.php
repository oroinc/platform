<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Symfony\Component\Form\FormBuilderInterface;

/**
 * OptionFormTypeBuilderInterface interface for theme configuration option builder(s)
 *
 * Services that will implement this interface will extend the available FormType(s) for the theme configuration section
 */
interface ConfigurationChildBuilderInterface
{
    public static function getType(): string;

    /**
     * Checks if the option type is supported by the builder.
     */
    public function supports(array $option): bool;

    /**
     * Adds an input of the appropriate type to the form, if supported.
     */
    public function buildOption(FormBuilderInterface $builder, array $option): void;
}
