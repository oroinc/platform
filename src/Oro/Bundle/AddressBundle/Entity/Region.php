<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Address region entity
 */
#[ORM\Entity(repositoryClass: RegionRepository::class)]
#[ORM\Table('oro_dictionary_region')]
#[ORM\Index(columns: ['name'], name: 'region_name_idx')]
#[Gedmo\TranslationEntity(class: RegionTranslation::class)]
#[Config(defaultValues: ['grouping' => ['groups' => ['dictionary']], 'dictionary' => ['search_fields' => ['name']]])]
class Region implements Translatable
{
    const SEPARATOR = '-';

    #[ORM\Id]
    #[ORM\Column(name: 'combined_code', type: Types::STRING, length: 16)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $combinedCode = null;

    #[ORM\ManyToOne(targetEntity: Country::class, cascade: ['persist'], inversedBy: 'regions')]
    #[ORM\JoinColumn(name: 'country_code', referencedColumnName: 'iso2_code')]
    protected ?Country $country = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 32)]
    protected ?string $code = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    #[ORM\Column(name: 'deleted', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $deleted = false;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @param string $combinedCode
     */
    public function __construct($combinedCode)
    {
        $this->combinedCode = $combinedCode;
    }

    /**
     * @param string $combinedCode
     * @return $this
     */
    public function setCombinedCode($combinedCode)
    {
        $this->combinedCode = $combinedCode;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getCombinedCode()
    {
        return $this->combinedCode;
    }

    /**
     * Set country
     *
     * @param  Country $country
     * @return Region
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Get country ISO2 code
     *
     * @return string|null
     */
    public function getCountryIso2Code()
    {
        return $this->country ? $this->country->getIso2Code() : null;
    }

    /**
     * Set code
     *
     * @param  string $code
     * @return Region
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return Region
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Region
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
        return (string) $this->getName();
    }

    /**
     * Get combined region code.
     *
     * @param string $country Country ISO2 code
     * @param string $region Region ISO2 code
     * @return string
     */
    public static function getRegionCombinedCode($country, $region)
    {
        return $country . self::SEPARATOR . $region;
    }
}
