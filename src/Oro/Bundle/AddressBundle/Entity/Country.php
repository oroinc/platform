<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroAddressBundle_Entity_Country;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Address country entity
 *
 * @mixin OroAddressBundle_Entity_Country
 */
#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table('oro_dictionary_country')]
#[ORM\Index(columns: ['name'], name: 'country_name_idx')]
#[Gedmo\TranslationEntity(class: CountryTranslation::class)]
#[Config(
    defaultValues: [
        'grouping' => ['groups' => ['dictionary']],
        'dictionary' => ['virtual_fields' => ['iso2Code', 'iso3Code', 'name'], 'search_fields' => ['name']]
    ]
)]
class Country implements Translatable, ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'iso2_code', type: Types::STRING, length: 2)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $iso2Code = null;

    #[ORM\Column(name: 'iso3_code', type: Types::STRING, length: 3)]
    protected ?string $iso3Code = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Gedmo\Translatable]
    protected ?string $name = null;

    /**
     * @var Collection<int, Region>
     */
    #[ORM\OneToMany(mappedBy: 'country', targetEntity: Region::class, cascade: ['ALL'], fetch: 'EXTRA_LAZY')]
    protected ?Collection $regions = null;

    #[Gedmo\Locale]
    protected ?string $locale = null;

    /**
     * @param string $iso2Code ISO2 country code
     */
    public function __construct($iso2Code)
    {
        $this->iso2Code = $iso2Code;
        $this->regions  = new ArrayCollection();
    }

    /**
     * Get iso2_code
     *
     * @return string
     */
    public function getIso2Code()
    {
        return $this->iso2Code;
    }

    /**
     * @param ArrayCollection $regions
     *
     * @return $this
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * @param Region $region
     * @return Country
     */
    public function addRegion(Region $region)
    {
        if (!$this->regions->contains($region)) {
            $this->regions->add($region);
            $region->setCountry($this);
        }

        return $this;
    }

    /**
     * @param Region $region
     * @return Country
     */
    public function removeRegion(Region $region)
    {
        if ($this->regions->contains($region)) {
            $this->regions->removeElement($region);
            $region->setCountry(null);
        }

        return $this;
    }

    /**
     * Check if country contains regions
     *
     * @return bool
     */
    public function hasRegions()
    {
        return count($this->regions) > 0;
    }

    /**
     * Set iso3_code
     *
     * @param  string  $iso3Code
     * @return Country
     */
    public function setIso3Code($iso3Code)
    {
        $this->iso3Code = $iso3Code;

        return $this;
    }

    /**
     * Get iso3_code
     *
     * @return string
     */
    public function getIso3Code()
    {
        return $this->iso3Code;
    }

    /**
     * Set country name
     *
     * @param  string  $name
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get country name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return Country
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
}
