<?php

namespace Oro\Bundle\ImportExportBundle\Formatter;

interface DateTimeTypeConverterInterface
{
    /**
     * Convert value to \DateTime object.
     *
     * @param string $value
     * @param string $type
     *
     * @return mixed
     */
    public function convertToDateTime($value, $type);
}
