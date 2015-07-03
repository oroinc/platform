<?php

namespace Oro\Bundle\UIBundle\Formatter;

interface FormatterInterface
{
    /**
     * Returns formatter name
     *
     * @return string
     */
    public function getFormatterName();

    /**
     * Applies formatter to the input parameter
     *
     * @param mixed $parameter
     * @param array $formatterArguments
     *
     * @return mixed
     */
    public function format($parameter, array $formatterArguments = []);

    /**
     * Default value if parameter is null
     *
     * @return mixed
     */
    public function getDefaultValue();

    /**
     * Returns array with supported field types
     *
     * @return array
     */
    public function getSupportedTypes();

    /**
     * Returns true if this formatter is default formatter
     *
     * @return boolean
     */
    public function isDefaultFormatter();
}
