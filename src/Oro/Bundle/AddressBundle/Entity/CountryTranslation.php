<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Represent Gedmo translation dictionary for Country entity.
 */
#[ORM\Entity(repositoryClass: CountryTranslationRepository::class)]
#[ORM\Table(name: 'oro_dictionary_country_trans')]
#[ORM\Index(columns: ['locale', 'object_class', 'field', 'foreign_key'], name: 'country_translation_idx')]
class CountryTranslation extends AbstractTranslation
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'foreign_key', type: Types::STRING, length: 2)]
    protected $foreignKey;

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getId();
    }
}
