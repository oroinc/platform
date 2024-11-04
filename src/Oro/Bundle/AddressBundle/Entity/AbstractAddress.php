<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;

/**
 * The base class for address entities.
 *
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractAddress implements EmptyItem, FullNameInterface, AddressInterface
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10]])]
    protected ?string $label = null;

    #[ORM\Column(name: 'street', type: Types::STRING, length: 500, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 80, 'identity' => true]])]
    protected ?string $street = null;

    #[ORM\Column(name: 'street2', type: Types::STRING, length: 500, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 90]])]
    protected ?string $street2 = null;

    #[ORM\Column(name: 'city', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 110, 'identity' => true]])]
    protected ?string $city = null;

    #[ORM\Column(name: 'postal_code', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 100, 'identity' => true]])]
    protected ?string $postalCode = null;

    /**
     * @var Country|null
     */
    #[ORM\ManyToOne(targetEntity: Country::class)]
    #[ORM\JoinColumn(name: 'country_code', referencedColumnName: 'iso2_code')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 140, 'short' => true, 'identity' => true]])]
    protected $country;

    /**
     * @var Region|null
     */
    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(name: 'region_code', referencedColumnName: 'combined_code')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 130, 'short' => true, 'identity' => true]])]
    protected $region;

    #[ORM\Column(name: 'organization', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected ?string $organization = null;

    #[ORM\Column(name: 'region_text', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 120]])]
    protected ?string $regionText = null;

    #[ORM\Column(name: 'name_prefix', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 30]])]
    protected ?string $namePrefix = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 40]])]
    protected ?string $firstName = null;

    #[ORM\Column(name: 'middle_name', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 50]])]
    protected ?string $middleName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 60]])]
    protected ?string $lastName = null;

    #[ORM\Column(name: 'name_suffix', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 70]])]
    protected ?string $nameSuffix = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.created_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(
        defaultValues: ['entity' => ['label' => 'oro.ui.updated_at'], 'importexport' => ['excluded' => true]]
    )]
    protected ?\DateTimeInterface $updated = null;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string
     */
    #[\Override]
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set street2
     *
     * @param string $street2
     * @return $this
     */
    public function setStreet2($street2)
    {
        $this->street2 = $street2;

        return $this;
    }

    /**
     * Get street2
     *
     * @return string
     */
    #[\Override]
    public function getStreet2()
    {
        return $this->street2;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    #[\Override]
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set region
     *
     * @param Region $region
     * @return $this
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set region text
     *
     * @param string $regionText
     * @return $this
     */
    public function setRegionText($regionText)
    {
        $this->regionText = $regionText;

        return $this;
    }

    /**
     * Get region test
     *
     * @return string
     */
    public function getRegionText()
    {
        return $this->regionText;
    }

    /**
     * Get name of region
     *
     * @return string
     */
    #[\Override]
    public function getRegionName()
    {
        return $this->getRegion() ? $this->getRegion()->getName() : $this->getRegionText();
    }

    /**
     * Get code of region
     *
     * @return string
     */
    #[\Override]
    public function getRegionCode()
    {
        return $this->getRegion() ? $this->getRegion()->getCode() : '';
    }

    /**
     * Get region or region string
     *
     * @return Region|string
     */
    public function getUniversalRegion()
    {
        if (!empty($this->regionText)) {
            return $this->regionText;
        }
        return $this->region;
    }

    /**
     * Set postal_code
     *
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postal_code
     *
     * @return string
     */
    #[\Override]
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set country
     *
     * @param Country $country
     * @return $this
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
     * Get name of country
     *
     * @return string
     */
    #[\Override]
    public function getCountryName()
    {
        return $this->getCountry() ? $this->getCountry()->getName() : '';
    }

    /**
     * Get country ISO3 code
     *
     * @return string
     */
    #[\Override]
    public function getCountryIso3()
    {
        return $this->getCountry() ? $this->getCountry()->getIso3Code() : '';
    }

    /**
     * Get country ISO2 code
     *
     * @return string
     */
    #[\Override]
    public function getCountryIso2()
    {
        return $this->getCountry() ? $this->getCountry()->getIso2Code() : '';
    }

    /**
     * Sets organization
     *
     * @param string $organization
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return string
     */
    #[\Override]
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set name prefix
     *
     * @param string $namePrefix
     * @return $this
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * Get name prefix
     *
     * @return string
     */
    #[\Override]
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * Set first name
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get first name
     *
     * @return string
     */
    #[\Override]
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set middle name
     *
     * @param string $middleName
     * @return $this
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * Get middle name
     *
     * @return string
     */
    #[\Override]
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set last name
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get last name
     *
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set name suffix
     *
     * @param string $nameSuffix
     * @return $this
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * Get name suffix
     *
     * @return string
     */
    #[\Override]
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * Get address created date/time
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set address created date/time
     *
     * @param \DateTime $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get address last update date/time
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set address updated date/time
     *
     * @param \DateTime $updated
     * @return $this
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Pre persist event listener
     */
    #[ORM\PrePersist]
    public function beforeSave()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updated = clone $this->created;
    }

    /**
     * Pre update event listener
     */
    #[ORM\PreUpdate]
    public function beforeUpdate()
    {
        $this->updated = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $this->created = null;
            $this->updated = null;
        }
    }

    /**
     * Convert address to string
     *
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        $data = [
            $this->getFirstName(),
            $this->getLastName(),
            ',',
            $this->getStreet(),
            $this->getStreet2(),
            $this->getCity(),
            $this->getUniversalRegion(),
            ',',
            $this->getCountry(),
            $this->getPostalCode(),
        ];

        return trim(implode(' ', $data), " \t\n\r\0\x0B,");
    }

    /**
     * Check if entity is empty.
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function isEmpty()
    {
        return empty($this->label)
            && empty($this->firstName)
            && empty($this->lastName)
            && empty($this->street)
            && empty($this->street2)
            && empty($this->city)
            && empty($this->region)
            && empty($this->regionText)
            && empty($this->country)
            && empty($this->postalCode);
    }

    /**
     * @param mixed $other
     * @return bool
     */
    public function isEqual($other)
    {
        $class = ClassUtils::getClass($this);

        if (!$other instanceof $class) {
            return false;
        }

        /** @var AbstractAddress $other */
        if ($this->getId() && $other->getId()) {
            return $this->getId() == $other->getId();
        }

        if ($this->getId() || $other->getId()) {
            return false;
        }

        return $this === $other;
    }
}
