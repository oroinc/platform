<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\NotificationBundle\Entity\NotificationEmailInterface;

/**
 * Organization
 *
 * @ORM\Table(name="oro_organization")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
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
class Organization implements NotificationEmailInterface
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
     * @ORM\Column(name="name", type="string", length=255)
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
     * @var \Datetime $created
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
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
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
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
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Expose
     * @Oro\Versioned
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $enabled = true;

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
        return (string) $this->getName();
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
        $this->enabled = (boolean) $enabled;

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
}
