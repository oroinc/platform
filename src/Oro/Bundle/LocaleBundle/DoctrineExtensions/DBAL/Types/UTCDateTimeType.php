<?php

namespace Oro\Bundle\LocaleBundle\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Doctrine DBAL Type for UTC DateTime
 */
class UTCDateTimeType extends DateTimeType
{
    /** @var null|\DateTimeZone */
    private static $utc = null;

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        /** @var \DateTime $value */
        $timezone = (self::$utc) ?: (self::$utc = new \DateTimeZone('UTC'));
        if ($value->getTimezone() !== $timezone) {
            $value->setTimezone($timezone);
        }

        return parent::convertToDatabaseValue($value, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $timezone = (self::$utc) ?: (self::$utc = new \DateTimeZone('UTC'));

        if ($value instanceof \DateTimeInterface && $value->getTimezone() !== $timezone) {
            $value->setTimezone($timezone);

            return $value;
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, $timezone);

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        $errors = $val->getLastErrors();
        // date was parsed to completely not valid value
        if ($errors && $errors['warning_count'] > 0 && (int)$val->format('Y') < 0) {
            return null;
        }

        return $val;
    }
}
