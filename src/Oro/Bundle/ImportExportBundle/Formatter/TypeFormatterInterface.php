<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

interface TypeFormatterInterface
{
    /**
     * Formats value by provided type.
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    public function formatType($value, $type);
}
