<?php

namespace Oro\Bundle\LocaleBundle\Converter;

class IntlDateTimeFormatConverter extends AbstractDateTimeFormatConverter
{
    const NAME = 'intl';

    /**
     * Returns INTL format without convert
     *
     * {@inheritDoc}
     */
    protected function convertFormat($format)
    {
        return $format;
    }
}
