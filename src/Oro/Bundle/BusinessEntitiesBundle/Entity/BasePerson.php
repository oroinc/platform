<?php

namespace Oro\Bundle\BusinessEntitiesBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;

/**
 * Basic business entity
 */
#[ORM\MappedSuperclass]
class BasePerson implements FullNameInterface, EmailHolderInterface
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name_prefix', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $namePrefix = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $firstName = null;

    #[ORM\Column(name: 'middle_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $middleName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $lastName = null;

    #[ORM\Column(name: 'name_suffix', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $nameSuffix = null;

    #[ORM\Column(name: 'gender', type: Types::STRING, length: 8, nullable: true)]
    protected ?string $gender = null;

    #[ORM\Column(name: 'birthday', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $birthday = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $email = null;

    /**
     * @var Collection<int, AbstractAddress>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: AbstractAddress::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['primary' => Criteria::DESC])]
    protected ?Collection $addresses = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * init addresses with empty collection
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function setNamePrefix($prefix)
    {
        $this->namePrefix = $prefix;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $middleName
     *
     * @return $this
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $nameSuffix
     *
     * @return $this
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * @param string $gender
     *
     * @return $this
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param \DateTime|null $birthday
     *
     * @return $this
     */
    public function setBirthday(?\DateTime $birthday = null)
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get contact last update date/time
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Set addresses.
     *
     * This method could not be named setAddresses because of bug CRM-253.
     *
     * @param Collection|AbstractAddress[] $addresses
     * @return BasePerson
     */
    public function resetAddresses($addresses)
    {
        $this->addresses->clear();

        foreach ($addresses as $address) {
            $this->addAddress($address);
        }

        return $this;
    }

    /**
     * Add address
     *
     * @param AbstractAddress $address
     * @return BasePerson
     */
    public function addAddress(AbstractAddress $address)
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }

        return $this;
    }

    /**
     * Remove address
     *
     * @param AbstractAddress $address
     * @return BasePerson
     */
    public function removeAddress(AbstractAddress $address)
    {
        if ($this->addresses->contains($address)) {
            $this->addresses->removeElement($address);
        }

        return $this;
    }

    /**
     * Get addresses
     *
     * @return Collection|AbstractAddress[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * @param AbstractAddress $address
     * @return bool
     */
    public function hasAddress(AbstractAddress $address)
    {
        return $this->getAddresses()->contains($address);
    }

    /**
     * Clone relations
     */
    public function __clone()
    {
        if ($this->addresses) {
            $this->addresses = clone $this->addresses;
        }
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        return (string)$this->getLastName() . (string)$this->getFirstName();
    }
}
