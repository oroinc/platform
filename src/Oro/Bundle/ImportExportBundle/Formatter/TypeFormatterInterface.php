<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

interface TypeFormatterInterface
{
    /**
     * @param mixed  $value
     * @param string $type
     * @return mixed
     */
    public function formatType($value, $type);
}
