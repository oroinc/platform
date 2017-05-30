<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use JMS\Serializer\Annotation as JMS;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * Continent
 *
 * @ORM\Table("oro_dictionary_continent", indexes={
 *      @ORM\Index(name="continent_name_idx", columns={"name"})
 * })
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="Oro\Bundle\AddressBundle\Entity\ContinentTranslation")
 * @Config(
 *      defaultValues={
 *          "grouping"={
 *              "groups"={"dictionary"}
 *          },
 *          "dictionary"={
 *              "virtual_fields"={"code", "name"}
 *          }
 *      }
 * )
 * @JMS\ExclusionPolicy("ALL")
 */
class Continent implements Translatable
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="code", type="string", length=2)
     * @JMS\Type("string")
     * @JMS\SerializedName("code")
     * @JMS\Expose
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $code;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\AddressBundle\Entity\Country",
     *     mappedBy="continent",
     *     cascade={"ALL"},
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $countries;

    /**
     * @Gedmo\Locale
     */
    protected $locale;

    /**
     * @param string $code ISO2 Continent code
     */
    public function __construct($code)
    {
        $this->code = $code;
        $this->countries = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param string $name
     * @return Continent
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

    /**
     * Add country
     *
     * @param \Oro\Bundle\AddressBundle\Entity\Country $country
     * @return Continent
     */
    public function addCountry(\Oro\Bundle\AddressBundle\Entity\Country $country)
    {
        $this->countries[] = $country;

        return $this;
    }

    /**
     * Remove country
     *
     * @param \Oro\Bundle\AddressBundle\Entity\Country $country
     */
    public function removeCountry(\Oro\Bundle\AddressBundle\Entity\Country $country)
    {
        $this->countries->removeElement($country);
    }

    /**
     * Get countries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCountries()
    {
        return $this->countries;
    }
}
