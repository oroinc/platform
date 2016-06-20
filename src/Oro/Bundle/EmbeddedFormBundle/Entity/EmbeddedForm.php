<?php

namespace Oro\Bundle\EmbeddedFormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EmbeddedFormBundle\Model\ExtendEmbeddedForm;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_embedded_form")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_embedded_form_list",
 *      defaultValues={
 *          "entity"={
 *              "category"="account_management"
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="owner_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "activity"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class EmbeddedForm extends ExtendEmbeddedForm
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type="string", name="id")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="title", type="text")
     *
     * @Assert\NotBlank()
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="css", type="text")
     *
     * @Assert\NotBlank()
     */
    protected $css;

    /**
     * @var string
     *
     * @ORM\Column(name="form_type", type="string", length=255)
     *
     * @Assert\NotBlank()
     */
    protected $formType;

    /**
     * @var string
     *
     * @ORM\Column(name="success_message", type="text")
     */
    protected $successMessage;

    /**
     * @var string
     *
     * @ORM\Column(name="allowed_domains", type="text", nullable=true)
     */
    protected $allowedDomains;

    /**
     * @var \DateTime $created
     *
     * @ORM\Column(name="created_at", type="datetime")
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
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated_at", type="datetime")
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
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $css
     */
    public function setCss($css)
    {
        $this->css = $css;
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param string $formType
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
    }

    /**
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $successMessage
     */
    public function setSuccessMessage($successMessage)
    {
        $this->successMessage = $successMessage;
    }

    /**
     * @return string
     */
    public function getSuccessMessage()
    {
        return $this->successMessage;
    }

    /**
     * @param string $allowedDomains
     */
    public function setAllowedDomains($allowedDomains)
    {
        $this->allowedDomains = $allowedDomains;
    }

    /**
     * @return string
     */
    public function getAllowedDomains()
    {
        return $this->allowedDomains;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
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
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->id = UUIDGenerator::v4();
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $organization
     */
    public function setOwner(Organization $organization)
    {
        $this->owner = $organization;
    }
}
