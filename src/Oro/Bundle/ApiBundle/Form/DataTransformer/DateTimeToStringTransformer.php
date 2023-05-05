<?php

namespace Oro\Bundle\ApiBundle\Form\DataTransformer;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * This data transformer is used to process datetime, date or time in RFC 3339 format.
 * @link https://www.w3.org/TR/NOTE-datetime
 */
class DateTimeToStringTransformer implements DataTransformerInterface
{
    private const DATE_VALUE = '(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?';
    private const TIME_VALUE = '(\d{2}):(\d{2})(?::(\d{2}))?(?:\.\d+)?';
    private const TIMEZONE_VALUE = '(Z|(?:(?:\+|-)\d{2}:\d{2}))';
    private const DATETIME_PATTERN =
        '/^' . self::DATE_VALUE . '(T' . self::TIME_VALUE . self::TIMEZONE_VALUE . ')?$/';
    private const DATE_PATTERN = '/^' . self::DATE_VALUE . '$/';
    private const TIME_PATTERN = '/^(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?$/';

    private bool $withTime;
    private bool $withDate;

    public function __construct(bool $withTime = true, bool $withDate = true)
    {
        if (!$withTime && !$withDate) {
            throw new \InvalidArgumentException('At least one of $withTime or $withDate should be set.');
        }
        $this->withTime = $withTime;
        $this->withDate = $withDate;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        return $this->transformValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        return $this->reverseTransformValue($value);
    }

    private function transformValue(\DateTimeInterface $value): string
    {
        if (!$this->withTime) {
            return $value->format('Y-m-d');
        }

        if (!$this->withDate) {
            return $value->format('H:i:s');
        }

        $result = $value->format('Y-m-d\TH:i:s.vP');
        $result = preg_replace('/\.000([\+\-]\d{2}:\d{2})$/', '$1', $result);
        $result = preg_replace('/\+00:00$/', 'Z', $result);

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function reverseTransformValue(string $value): \DateTimeInterface
    {
        if (!$this->withTime) {
            return $this->reverseTransformDateValue($value);
        }

        if (!$this->withDate) {
            return $this->reverseTransformTimeValue($value);
        }

        if (!preg_match(self::DATETIME_PATTERN, $value, $matches)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The value "%s" is not a valid datetime.', $value),
                'oro.api.form.invalid_datetime',
                ['{{ value }}' => $value]
            );
        }
        if (\array_key_exists(3, $matches) && '' !== $matches[3]) {
            $this->assertValidDate($matches[1], $matches[2], $matches[3]);
            $dateValue = $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        } elseif (\array_key_exists(2, $matches) && '' !== $matches[2]) {
            $this->assertValidDate($matches[1], $matches[2], '01');
            $dateValue = $matches[1] . '-' . $matches[2] . '-01';
        } else {
            $this->assertValidDate($matches[1], '01', '01');
            $dateValue = $matches[1] . '-01-01';
        }
        if (\array_key_exists(7, $matches) && '' !== $matches[7]) {
            $this->assertValidTime($matches[5], $matches[6], $matches[7]);
            $value = $dateValue . $matches[4];
        } elseif (\array_key_exists(5, $matches)) {
            $this->assertValidTime($matches[5], $matches[6], '00');
            $value = $dateValue . 'T' . $matches[5] . ':' . $matches[6] . ':00' . $matches[8];
        } else {
            $value = $dateValue . 'T00:00:00Z';
        }

        return $this->convertToDateTime($value);
    }

    private function reverseTransformDateValue(string $value): \DateTimeInterface
    {
        if (!preg_match(self::DATE_PATTERN, $value, $matches)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The value "%s" is not a valid date.', $value),
                'oro.api.form.invalid_date',
                ['{{ value }}' => $value]
            );
        }
        if (\array_key_exists(3, $matches)) {
            $this->assertValidDate($matches[1], $matches[2], $matches[3]);
        } elseif (\array_key_exists(2, $matches)) {
            $this->assertValidDate($matches[1], $matches[2], '01');
            $value .= '-01';
        } else {
            $this->assertValidDate($matches[1], '01', '01');
            $value .= '-01-01';
        }

        return $this->convertToDateTime($value . 'T00:00:00Z');
    }

    private function reverseTransformTimeValue(string $value): \DateTimeInterface
    {
        if (!preg_match(self::TIME_PATTERN, $value, $matches)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The value "%s" is not a valid time.', $value),
                'oro.api.form.invalid_time',
                ['{{ value }}' => $value]
            );
        }
        if (\array_key_exists(3, $matches)) {
            $this->assertValidTime($matches[1], $matches[2], $matches[3]);
        } else {
            $this->assertValidTime($matches[1], $matches[2], '00');
            $value .= ':00';
        }

        // Setting the timezone is important, otherwise timestamp 0 produces
        // "1969-12-31" in GMT-x timezones, and "1970-01-01" in GMT+x timezones.
        $beginningOfTime = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'))->setTimestamp(0);

        return $this->convertToDateTime($beginningOfTime->format('Y-m-d\T') . $value . 'Z');
    }

    private function convertToDateTime(string $value): \DateTimeInterface
    {
        try {
            $result = new \DateTime($value);
        } catch (\Exception $e) {
            throw new TransformationFailedException(
                $e->getMessage(),
                $e->getCode(),
                $e,
                'oro.api.form.invalid_datetime',
                ['{{ value }}' => $value]
            );
        }

        if ($result->getTimezone()->getName() !== 'UTC') {
            $result->setTimezone(new \DateTimeZone('UTC'));
        }

        return $result;
    }

    private function assertValidDate(string $year, string $month, string $day): void
    {
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The date "%s-%s-%s" is not a valid date.', $year, $month, $day),
                'oro.api.form.invalid_date',
                ['{{ value }}' => sprintf('%s-%s-%s', $year, $month, $day)]
            );
        }
    }

    private function assertValidTime(string $hours, string $minutes, string $seconds): void
    {
        if ((int)$hours > 23 || (int)$minutes > 59 || (int)$seconds > 59) {
            throw FormUtil::createTransformationFailedException(
                sprintf('The time "%s:%s:%s" is not a valid time.', $hours, $minutes, $seconds),
                'oro.api.form.invalid_time',
                ['{{ value }}' => sprintf('%s:%s:%s', $hours, $minutes, $seconds)]
            );
        }
    }
}
