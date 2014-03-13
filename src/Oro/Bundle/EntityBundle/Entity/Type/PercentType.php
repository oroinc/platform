<?php

namespace Oro\Bundle\EntityBundle\Entity\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class PercentType extends Type
{
    const PERCENT_TYPE   = 'percent';

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
        return $platform->getFloatDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return (null === $value) ? null : $value;
    }
}
