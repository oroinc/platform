<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * EnumValueTranslation class to host translated entity enum value properties
 *
 * @deprecated
 */
class EnumValueTranslation extends AbstractTranslation
{
    /**
     * @var string|null
     */
    protected $foreignKey;

    /**
     * @var string|null
     */
    protected $field;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
