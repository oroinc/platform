<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeConverterInterface;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;

class DateTimeNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var TypeFormatterInterface
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $defaultDateTimeFormat;

    /**
     * @var string
     */
    protected $defaultDateFormat;

    /**
     * @var string
     */
    protected $defaultTimeFormat;

    /**
     * @var \DateTimeZone
     */
    protected $defaultTimezone;

    public function __construct(
        $defaultDateTimeFormat = \DateTime::ISO8601,
        $defaultDateFormat = 'Y-m-d',
        $defaultTimeFormat = 'H:i:s',
        $defaultTimezone = 'UTC'
    ) {
        $this->defaultDateTimeFormat = $defaultDateTimeFormat;
        $this->defaultDateFormat = $defaultDateFormat;
        $this->defaultTimeFormat = $defaultTimeFormat;
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
    }

    /**
     * @param TypeFormatterInterface $formatter
     */
    public function setFormatter(TypeFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param \DateTime $object
     * @param mixed $format
     * @param array $context
     * @return string
     */
    public function normalize($object, $format = null, array $context = array())
    {
        if (!empty($context['format'])) {
            return $object->format($context['format']);
        }

        if (!empty($context['type']) && in_array($context['type'], ['datetime', 'date', 'time'], true)) {
            if ($this->formatter !== null && $this->formatter instanceof TypeFormatterInterface) {
                return $this->formatter->formatType($object, $context['type']);
            }
        }

        return $object->format($this->getFormat($context));
    }

    /**
     * @param mixed  $data
     * @param string $class
     * @param mixed  $format
     * @param array  $context
     *
     * @return \DateTime|null
     * @throws RuntimeException
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        if (empty($data)) {
            return null;
        }

        $format   = $this->getFormat($context);
        $timezone = $this->getTimezone($context);
        $datetime = false;

        if (!empty($context['format'])) {
            $datetime = \DateTime::createFromFormat($context['format'] . '|', (string)$data, $timezone);
        } elseif (!empty($context['type']) && in_array($context['type'], ['datetime', 'date', 'time'], true)) {
            $formatter = $this->formatter;
            if (null !== $formatter && $formatter instanceof DateTimeTypeConverterInterface) {
                /** @var DateTimeTypeConverterInterface $formatter */
                $datetime = $formatter->convertToDateTime($data, $context['type']);
            }
        }

        // Default denormalization
        if (false === $datetime) {
            $datetime = \DateTime::createFromFormat($format . '|', (string)$data, $timezone);
        }

        // If we are denormalizing date or time for backward compatibility try to denormalize as dateTime
        if (false === $datetime
            && array_key_exists('type', $context)
            && in_array($context['type'], ['date', 'time'], true)
        ) {
            $datetime = \DateTime::createFromFormat($this->defaultDateTimeFormat . '|', (string) $data, $timezone);
        }

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        return $datetime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return $data instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return is_string($data) && $type === 'DateTime';
    }

    /**
     * Gets format from $context.
     * In cases when format or type is not specified, default format used.
     *
     * @param array $context
     *
     * @return string
     */
    protected function getFormat(array $context)
    {
        if (!empty($context['format'])) {
            return $context['format'];
        }

        if (!empty($context['type'])) {
            switch ($context['type']) {
                case 'date':
                    return $this->defaultDateFormat;
                case 'time':
                    return $this->defaultTimeFormat;
                default:
                    return $this->defaultDateTimeFormat;
            }
        }

        return $this->defaultDateTimeFormat;
    }

    /**
     * @param array $context
     * @return \DateTimeZone
     */
    protected function getTimezone(array $context)
    {
        return isset($context['timezone']) ? $context['timezone'] : $this->defaultTimezone;
    }
}
