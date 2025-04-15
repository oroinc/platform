<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Address country entity
 *
 * @ORM\Table("oro_dictionary_country", indexes={
 *      @ORM\Index(name="country_name_idx", columns={"name"})
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository")
 * @Gedmo\TranslationEntity(class="Oro\Bundle\AddressBundle\Entity\CountryTranslation")
 * @Config(
 *      defaultValues={
 *          "grouping"={
 *              "groups"={"dictionary"}
 *          },
 *          "dictionary"={
 *              "virtual_fields"={"iso2Code", "iso3Code", "name"},
 *              "search_fields"={"name"}
 *          }
 *      }
 * )
 */
class Country implements Translatable, ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="iso2_code", type="string", length=2)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $iso2Code;

    /**
     * @var string
     *
     * @ORM\Column(name="iso3_code", type="string", length=3)
     */
    protected $iso3Code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     */
    protected $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\AddressBundle\Entity\Region",
     *     mappedBy="country",
     *     cascade={"ALL"},
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $regions;

    /**
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * @var bool
     *
     * @ORM\Column(name="deleted", type="boolean", options={"default"=false})
     */
    protected $deleted = false;


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
     * @param \Doctrine\Common\Collections\ArrayCollection $regions
     *
     * @return $this
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
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

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }
}
