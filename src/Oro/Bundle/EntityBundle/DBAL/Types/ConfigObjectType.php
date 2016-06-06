<?php

namespace Oro\Bundle\EntityBundle\DBAL\Types;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;

use Oro\Component\Config\Common\ConfigObject;

class ConfigObjectType extends JsonArrayType
{
    const TYPE = 'config_object';

    /** {@inheritdoc} */
    public function getName()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
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
