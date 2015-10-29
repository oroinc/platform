<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;
use Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter;

class ExcelDateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var FormatterProvider
     */
    protected $formatterProvider;

    /**
     * @param FormatterProvider $formatterProvider
     */
    public function __construct(FormatterProvider $formatterProvider)
    {
        $this->formatterProvider = $formatterProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return
            $data instanceof \DateTime &&
            isset($context[FormatterProvider::FORMAT_TYPE]) &&
            $context[FormatterProvider::FORMAT_TYPE] === 'excel' &&
            in_array($context['type'], ['datetime', 'date', 'time'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $type       = $context['type'];
        $formatType = $context[FormatterProvider::FORMAT_TYPE];
        $formatter  = $this->formatterProvider->getFormatterFor($formatType, $type);

        return $formatter->formatType($object, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return
            is_string($data) && $type === 'DateTime' &&
            isset($context[FormatterProvider::FORMAT_TYPE]) &&
            $context[FormatterProvider::FORMAT_TYPE] === 'excel' &&
            in_array($context['type'], ['datetime', 'date', 'time'], true);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $type       = $context['type'];
        $timezone   = isset($context['timezone']) ? $context['timezone'] : 'UTC';
        $formatType = $context[FormatterProvider::FORMAT_TYPE];

        /** @var ExcelDateTimeTypeFormatter $formatter */
        $formatter = $this->formatterProvider->getFormatterFor($formatType, $type);

        $dateFormat = $timeFormat = \IntlDateFormatter::SHORT;
        if ($type === 'date') {
            $timeFormat = \IntlDateFormatter::NONE;
        } elseif ($type === 'time') {
            $dateFormat = \IntlDateFormatter::NONE;
        }

        $intlFormatter = $formatter->getIntlFormatter($dateFormat, $timeFormat, $timezone);
        $pattern       = $formatter->getPattern($dateFormat, $timeFormat);
        $intlFormatter->setPattern($pattern);

        $timestamp = $intlFormatter->parse($data);

        if (intl_get_error_code() != 0) {
            throw new \Exception(intl_get_error_message());
        }

        $datetime = $formatter->getDateTimeFromTimestamp($timestamp);

        return $datetime;
    }

}
