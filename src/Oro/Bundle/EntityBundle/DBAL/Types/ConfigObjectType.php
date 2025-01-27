<?php

namespace Oro\Bundle\EntityBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Oro\Component\Config\Common\ConfigObject;

/**
 * Type that maps a PHP array to a JSON object in the database
 */
class ConfigObjectType extends JsonType
{
    const TYPE = 'config_object';

    #[\Override]
    public function getName()
    {
        return self::TYPE;
    }

    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        $valueToDb = $value instanceof ConfigObject ? $value->toArray() : [];

        return parent::convertToDatabaseValue($valueToDb, $platform);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return mixed
     * @throws ConversionException
     */
    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return null;
        }

        $convertedValue = parent::convertToPHPValue($value, $platform);
        if (! is_array($convertedValue)) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return ConfigObject::create($convertedValue);
    }
}
