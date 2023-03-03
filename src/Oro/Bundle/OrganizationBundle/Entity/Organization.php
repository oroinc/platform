<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Organization represents a real enterprise, business, firm, company or another organization, to which the users belong
 *
 * @ORM\Table(name="oro_organization")
 * @ORM\Entity(repositoryClass="Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(
 *     fields={"name"}
 * )
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management"
 *          },
 *          "form"={
 *              "form_type"="Oro\Bundle\OrganizationBundle\Form\Type\OrganizationSelectType"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *      }
 * )
 */
class Organization implements OrganizationInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @ConfigField(
     *  defaultValues={
     *    "dataaudit"={
     *       "auditable"=true
     *    },
     *    "importexport"={
     *       "identity"=true
     *    }
     *   }
     * )
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *  defaultValues={
     *    "dataaudit"={
     *       "auditable"=true
     *    }
     *   }
     * )
     */
    protected $description;

    /**
     * @var ArrayCollection|BusinessUnit[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnit",
     *     mappedBy="organization",
     *     cascade={"ALL"},
     *     fetch="EXTRA_LAZY"
     * )
     */
    protected $businessUnits;

    /**
     * @var ArrayCollection|User[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User", mappedBy="organizations")
     * @ORM\JoinTable(name="oro_user_organization")
     */
    protected $users;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default"="1"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $enabled = true;

    public function __construct()
    {
        $this->businessUnits = new ArrayCollection();
        $this->users         = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Organization
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Organization
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
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
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
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @param ArrayCollection $businessUnits
     *
     * @return $this
     */
    public function setBusinessUnits($businessUnits)
    {
        $this->businessUnits = $businessUnits;

        return $this;
    }

    /**
     * @return ArrayCollection|BusinessUnit[]
     */
    public function getBusinessUnits()
    {
        return $this->businessUnits;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return self
     */
    public function addBusinessUnit(BusinessUnit $businessUnit)
    {
        if (!$this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->add($businessUnit);
        }

        return $this;
    }

    /**
     * @param BusinessUnit $businessUnit
     *
     * @return self
     */
    public function removeBusinessUnit(BusinessUnit $businessUnit)
    {
        if ($this->businessUnits->contains($businessUnit)) {
            $this->businessUnits->removeElement($businessUnit);
        }

        return $this;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Pre persist event handler
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->enabled,
            $this->id,
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->name,
            $this->enabled,
            $this->id,
        ] = $serialized;
    }

    /**
     * Add User to Organization
     *
     * @param User $user
     * @return $this
     */
    public function addUser(User $user)
    {
        if (!$this->hasUser($user)) {
            $this->getUsers()->add($user);
            $user->addOrganization($this);
        }

        return $this;
    }

    /**
     * Delete User from Organization
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user)
    {
        if ($this->hasUser($user)) {
            $this->getUsers()->removeElement($user);
            $user->removeOrganization($this);
        }

        return $this;
    }

    /**
     * Check if organization has specified user assigned to it
     *
     * @param User $user
     * @return bool
     */
    public function hasUser(User $user)
    {
        return $this->getUsers()->contains($user);
    }

    /**
     * @return ArrayCollection|User[]
     */
    public function getUsers()
    {
        return $this->users;
    }
}
