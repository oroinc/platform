<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Table(name="oro_enum_value_trans", indexes={
 *      @ORM\Index(name="oro_enum_value_trans_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
class EnumValueTranslation extends AbstractTranslation
{
    /**
     * @var string
     *
     * @ORM\Column(name="foreign_key", type="string", length=32)
     */
    protected $foreignKey;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=4)
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
