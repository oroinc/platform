<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Represent Gedmo translation dictionary for Country entity.
 *
 * @ORM\Table(name="oro_dictionary_country_trans", indexes={
 *      @ORM\Index(name="country_translation_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity()
 */
class CountryTranslation extends AbstractTranslation
{
    /**
     * @var string $foreignKey
     *
     * @ORM\Column(name="foreign_key", type="string", length=2)
     */
    protected $foreignKey;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
