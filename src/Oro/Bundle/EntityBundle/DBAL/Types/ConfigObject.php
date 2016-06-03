<?php

namespace Oro\Bundle\EntityBundle\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonArrayType;

use Oro\Component\Config\Common\ConfigObject as Config;

class ConfigObject extends JsonArrayType
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
        $valueToDb = $value instanceof Config ? $value->toArray() : [];

        return parent::convertToDatabaseValue($valueToDb, $platform);
    }

    /**
     * @param mixed            $value
     * @param AbstractPlatform $platform
     *
     * @return Config
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $convertedValue = parent::convertToPHPValue($value, $platform);
        if (!is_array($convertedValue)) {
            $convertedValue = [];
        }

        return Config::create($convertedValue);
    }
}
