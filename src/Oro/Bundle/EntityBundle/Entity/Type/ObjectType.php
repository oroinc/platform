<?php

namespace Oro\Bundle\EntityBundle\Entity\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ObjectType as BaseObjectType;

class ObjectType extends BaseObjectType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $value = base64_decode($value);
        return parent::convertToPHPValue($value, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        $convertedValue = parent::convertToDatabaseValue($value, $platform);
        return base64_encode($convertedValue);
    }
}
