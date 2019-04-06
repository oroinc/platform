<?php

namespace Oro\Bundle\UIBundle\Formatter;

/**
 * Defines a mechanism for adding new formatters.
 * @see \Oro\Bundle\UIBundle\Formatter\FormatterManager
 * @see \Oro\Bundle\UIBundle\Twig\FormatExtension
 */
interface FormatterInterface
{
    /**
     * Formats the given value.
     *
     * @param mixed $value
     * @param array $formatterArguments
     *
     * @return mixed
     */
    public function format($value, array $formatterArguments = []);

    /**
     * Gets the default value if a value is NULL.
     *
     * @return mixed
     */
    public function getDefaultValue();
}
