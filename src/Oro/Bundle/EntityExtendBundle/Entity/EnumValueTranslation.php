<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Represents Gedmo translation dictionary for EnumValue entity.
 */
#[ORM\Entity(repositoryClass: TranslationRepository::class)]
#[ORM\Table(name: 'oro_enum_value_trans')]
#[ORM\Index(columns: ['locale', 'object_class', 'field', 'foreign_key'], name: 'oro_enum_value_trans_idx')]
class EnumValueTranslation extends AbstractTranslation
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'foreign_key', type: Types::STRING, length: 32)]
    protected $foreignKey;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 4)]
    protected $field;

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
