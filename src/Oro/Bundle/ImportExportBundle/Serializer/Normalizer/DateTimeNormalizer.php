<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\DateTimeTypeConverterInterface;
use Oro\Bundle\ImportExportBundle\Formatter\TypeFormatterInterface;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Converts a formatted datetime string into a \DateTime object and vice versa.
 */
class DateTimeNormalizer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    protected ?TypeFormatterInterface $formatter = null;
    protected string $defaultDateTimeFormat;
    protected string $defaultDateFormat;
    protected string $defaultTimeFormat;
    protected \DateTimeZone $defaultTimezone;

    public function __construct(
        string $defaultDateTimeFormat = \DateTime::ISO8601,
        string $defaultDateFormat = 'Y-m-d',
        string $defaultTimeFormat = 'H:i:s',
        string $defaultTimezone = 'UTC'
    ) {
        $this->defaultDateTimeFormat = $defaultDateTimeFormat;
        $this->defaultDateFormat = $defaultDateFormat;
        $this->defaultTimeFormat = $defaultTimeFormat;
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
    }

    public function setFormatter(TypeFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param \DateTime $object
     *
     * {@inheritdoc}
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        if (!empty($context['format'])) {
            return $object->format($context['format']);
        }

        if (!empty($context['type']) && in_array($context['type'], ['datetime', 'date', 'time'], true)) {
            if ($this->formatter instanceof TypeFormatterInterface) {
                return $this->formatter->formatType($object, $context['type']);
            }
        }

        return $object->format($this->getFormat($context));
    }

    /**
     * {@inheritdoc}
     *
     * @return \DateTime|null
     *
     * @throws RuntimeException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if (empty($data)) {
            return null;
        }

        $dateFormat = $this->getFormat($context);
        $timezone = $this->getTimezone($context);
        $datetime = false;

        if (!empty($context['format'])) {
            $datetime = \DateTime::createFromFormat($context['format'] . '|', (string)$data, $timezone);
        } elseif (!empty($context['type']) && in_array($context['type'], ['datetime', 'date', 'time'], true)) {
            if ($this->formatter instanceof DateTimeTypeConverterInterface) {
                /** @var DateTimeTypeConverterInterface $formatter */
                $datetime = $this->formatter->convertToDateTime($data, $context['type']);
            }
        }

        // Default denormalization
        if (false === $datetime) {
            $datetime = \DateTime::createFromFormat($dateFormat . '|', (string)$data, $timezone);
        }

        // If we are denormalizing date or time for backward compatibility try to denormalize as dateTime
        if (false === $datetime
            && array_key_exists('type', $context)
            && in_array($context['type'], ['date', 'time'], true)
        ) {
            $datetime = \DateTime::createFromFormat($this->defaultDateTimeFormat . '|', (string)$data, $timezone);
        }

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $dateFormat));
        }

        return $datetime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof \DateTime;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
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
     *
     * @return \DateTimeZone
     */
    protected function getTimezone(array $context)
    {
        return $context['timezone'] ?? $this->defaultTimezone;
    }
}
