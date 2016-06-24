<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use Oro\Bundle\NotificationBundle\Entity\NotificationEmailInterface;
use Oro\Bundle\OrganizationBundle\Model\ExtendOrganization;

/**
 * Organization
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
 *              "form_type"="oro_organization_select"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class Organization extends ExtendOrganization implements
    OrganizationInterface,
    NotificationEmailInterface,
    \Serializable
{
    /**
     * @var integer
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
     * @var ArrayCollection
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
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\UserBundle\Entity\User", mappedBy="organizations")
     * @ORM\JoinTable(name="oro_user_organization")
     */
    protected $users;

    /**
     * @var \Datetime $created
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
     * @var \Datetime $updated
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
     * @var boolean
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
    protected $enabled;

    public function __construct()
    {
        parent::__construct();

        $this->businessUnits = new ArrayCollection();
        $this->users         = new ArrayCollection();
    }


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
     * @param \Datetime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \Datetime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \Datetime
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
     * @return ArrayCollection
     */
    public function getBusinessUnits()
    {
        return $this->businessUnits;
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationEmails()
    {
        $emails = [];
        $this->businessUnits->forAll(
            function (BusinessUnit $bu) use (&$emails) {
                $emails = array_merge($emails, $bu->getNotificationEmails());
            }
        );

        return new ArrayCollection($emails);
    }

    /**
     * @param  bool $enabled User state
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (boolean)$enabled;

        return $this;
    }

    /**
     * @return Boolean true if organization is enabled, false otherwise
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

    /**
     * Serializes organization
     *
     * @return string
     */
    public function serialize()
    {
        $result = serialize(
            array(
                $this->name,
                $this->enabled,
                $this->id,
            )
        );
        return $result;
    }

    /**
     * Unserializes organization
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list(
            $this->name,
            $this->enabled,
            $this->id,
            ) = unserialize($serialized);
    }

    /**
     * @param ArrayCollection $users
     * @deprecated since 1.6
     */
    public function setUsers(ArrayCollection $users)
    {
        $this->users = $users;
    }

    /**
     * Add User to Organization
     *
     * @param User $user
     */
    public function addUser(User $user)
    {
        if (!$this->hasUser($user)) {
            $this->getUsers()->add($user);
            $user->addOrganization($this);
        }
    }

    /**
     * Delete User from Organization
     *
     * @param User $user
     */
    public function removeUser(User $user)
    {
        if ($this->hasUser($user)) {
            $this->getUsers()->removeElement($user);
            $user->removeOrganization($this);
        }
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
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
