<?php

namespace Oro\Bundle\EntityBundle\Entity\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class PercentType extends Type
{
    const PERCENT_TYPE = 'percent';
    const TYPE_PRECISION = 5;
    const TYPE_SCALE = 2;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::PERCENT_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $fieldDeclaration['precision'] = self::TYPE_PRECISION;
        $fieldDeclaration['scale'] = self::TYPE_SCALE;
        return $platform->getDecimalTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : $value;
    }
}
