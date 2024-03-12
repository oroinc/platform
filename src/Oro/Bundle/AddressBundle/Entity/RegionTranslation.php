<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Represent Gedmo translation dictionary for Region entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_dictionary_region_trans')]
#[ORM\Index(columns: ['locale', 'object_class', 'field', 'foreign_key'], name: 'region_translation_idx')]
class RegionTranslation extends AbstractTranslation
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'foreign_key', type: Types::STRING, length: 16)]
    protected $foreignKey;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
