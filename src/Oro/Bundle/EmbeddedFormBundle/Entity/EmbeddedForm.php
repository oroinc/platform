<?php

namespace Oro\Bundle\EmbeddedFormBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEmbeddedFormBundle_Entity_EmbeddedForm;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The embedded form configuration.
 *
 * @mixin OroEmbeddedFormBundle_Entity_EmbeddedForm
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_embedded_form')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_embedded_form_list',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true]
    ]
)]
class EmbeddedForm implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::STRING)]
    protected ?string $id = null;

    #[ORM\Column(name: 'title', type: Types::TEXT)]
    #[Assert\NotBlank]
    protected ?string $title = null;

    #[ORM\Column(name: 'css', type: Types::TEXT)]
    #[Assert\NotBlank]
    protected ?string $css = null;

    #[ORM\Column(name: 'form_type', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    protected ?string $formType = null;

    #[ORM\Column(name: 'success_message', type: Types::TEXT)]
    protected ?string $successMessage = null;

    #[ORM\Column(name: 'allowed_domains', type: Types::TEXT, nullable: true)]
    protected ?string $allowedDomains = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.created_at']])]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    #[ConfigField(defaultValues: ['entity' => ['label' => 'oro.ui.updated_at']])]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

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

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
        $this->id = UUIDGenerator::v4();
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(Organization $organization)
    {
        $this->owner = $organization;
    }
}
