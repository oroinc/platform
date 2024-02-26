<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Address type entity
 */
#[ORM\Entity(repositoryClass: AddressTypeRepository::class)]
#[ORM\Table(name: 'oro_address_type')]
#[Gedmo\TranslationEntity(class: AddressTypeTranslation::class)]
#[Config(defaultValues: ['grouping' => ['groups' => ['dictionary']]])]
class AddressType implements Translatable
{
    const TYPE_BILLING  = 'billing';
    const TYPE_SHIPPING = 'shipping';

    #[ORM\Column(name: 'name', type: Types::STRING, length: 16)]
    #[ORM\Id]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, unique: true)]
    #[Gedmo\Translatable]
    protected ?string $label = null;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get type name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set address type label
     *
     * @param string $label
     * @return AddressType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get address type label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return AddressType
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Returns locale code
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->label;
    }
}
