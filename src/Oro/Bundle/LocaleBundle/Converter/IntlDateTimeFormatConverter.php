<?php

namespace Oro\Bundle\LocaleBundle\Converter;

/**
 * Converts date/time formats to INTL format without modification.
 *
 * This converter implements the {@see DateTimeFormatConverterInterface} to provide INTL format
 * (as used by PHP's {@see \IntlDateFormatter}) without any conversion. It serves as a pass-through
 * converter for cases where the source format is already in INTL format.
 */
class IntlDateTimeFormatConverter extends AbstractDateTimeFormatConverter
{
    public const NAME = 'intl';

    /**
     * Returns INTL format without convert
     *
     */
    #[\Override]
    protected function convertFormat($format)
    {
        return $format;
    }
}
