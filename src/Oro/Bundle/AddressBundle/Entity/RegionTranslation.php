<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * Represent Gedmo translation dictionary for Region entity.
 *
 * @ORM\Table(name="oro_dictionary_region_trans", indexes={
 *      @ORM\Index(name="region_translation_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository")
 */
class RegionTranslation extends AbstractTranslation
{
    /**
     * @var string $foreignKey
     *
     * @ORM\Column(name="foreign_key", type="string", length=16)
     */
    protected $foreignKey;

    /**
     * @var string $content
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $content;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
