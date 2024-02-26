<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEmailBundle_Entity_EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents localizable email template which is used for template email notifications sending.
 *
 * @mixin OroEmailBundle_Entity_EmailTemplate
 */
#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
#[ORM\Table(name: 'oro_email_template')]
#[ORM\Index(columns: ['name'], name: 'email_name_idx')]
#[ORM\Index(columns: ['isSystem'], name: 'email_is_system_idx')]
#[ORM\Index(columns: ['entityName'], name: 'email_entity_name_idx')]
#[ORM\UniqueConstraint(name: 'UQ_NAME', columns: ['name', 'entityName'])]
#[Config(
    routeName: 'oro_email_emailtemplate_index',
    defaultValues: [
        'ownership' => [
            'owner_type' => 'USER',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'user_owner_id',
            'organization_field_name' => 'organization',
            'organization_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management'],
        'activity' => ['immutable' => true],
        'attachment' => ['immutable' => true]
    ]
)]
class EmailTemplate implements EmailTemplateInterface, ExtendEntityInterface
{
    use ExtendEntityTrait;

    public const TYPE_HTML = 'html';
    public const TYPE_TEXT = 'txt';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'isSystem', type: Types::BOOLEAN)]
    protected ?bool $isSystem = null;

    #[ORM\Column(name: 'isEditable', type: Types::BOOLEAN)]
    protected ?bool $isEditable = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    protected ?string $name = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?User $owner = null;

    #[ORM\Column(name: 'parent', type: Types::INTEGER, nullable: true)]
    protected ?int $parent = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'content', type: Types::TEXT, nullable: true)]
    protected ?string $content = null;

    #[ORM\Column(name: 'entityName', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $entityName = null;

    /**
     * Template type:
     *  - html
     *  - text
     */
    #[ORM\Column(name: 'type', type: Types::STRING, length: 20)]
    protected ?string $type = 'html';

    /**
     * @var Collection<int, EmailTemplateTranslation>
     */
    #[ORM\OneToMany(
        mappedBy: 'template',
        targetEntity: EmailTemplateTranslation::class,
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY'
    )]
    protected ?Collection $translations = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    protected ?bool $visible = true;

    /**
     * @param        $name
     * @param string $content
     * @param string $type
     * @param bool $isSystem
     */
    public function __construct($name = '', $content = '', $type = 'html', $isSystem = false)
    {
        // name can be overridden from email template
        $this->name = $name;
        // isSystem can be overridden from email template
        $this->isSystem = $isSystem;
        // isEditable can be overridden from email template
        $this->isEditable = false;

        $parsedContent = self::parseContent($content);
        foreach ($parsedContent['params'] as $param => $val) {
            $this->$param = $val;
        }

        // make sure that user's template is editable
        if (!$this->isSystem && !$this->isEditable) {
            $this->isEditable = true;
        }

        $this->type = $type;
        $this->content = $parsedContent['content'];
        $this->translations = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     *
     * @return EmailTemplate
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
     * Gets owning user
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Sets owning user
     *
     * @param User $owningUser
     *
     * @return EmailTemplate
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Set parent
     *
     * @param integer $parent
     *
     * @return EmailTemplate
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return integer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set entityName
     *
     * @param string $entityName
     *
     * @return EmailTemplate
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;

        return $this;
    }

    /**
     * Get entityName
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Set a flag indicates whether a template is system or not.
     *
     * @param boolean $isSystem
     *
     * @return EmailTemplate
     */
    public function setIsSystem($isSystem)
    {
        $this->isSystem = $isSystem;

        return $this;
    }

    /**
     * Get a flag indicates whether a template is system or not.
     * System templates cannot be removed or changed.
     *
     * @return boolean
     */
    public function getIsSystem()
    {
        return $this->isSystem;
    }

    /**
     * Get a flag indicates whether a template can be changed.
     *
     * @param boolean $isEditable
     *
     * @return $this
     */
    public function setIsEditable($isEditable)
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    /**
     * Get a flag indicates whether a template can be changed.
     * For user's templates this flag has no sense (these templates always have this flag true)
     * But editable system templates can be changed (but cannot be removed or renamed).
     *
     * @return boolean
     */
    public function getIsEditable()
    {
        return $this->isEditable;
    }

    /**
     * Set template type
     *
     * @param string $type
     *
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get template type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param Collection $translations
     * @return EmailTemplate
     */
    public function setTranslations(iterable $translations): self
    {
        foreach ($this->translations as $translation) {
            $this->removeTranslation($translation);
        }

        foreach ($translations as $translation) {
            $this->addTranslation($translation);
        }

        return $this;
    }

    /**
     * @return Collection|EmailTemplateTranslation[]
     */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(EmailTemplateTranslation $translation): self
    {
        if (!$this->translations->contains($translation)) {
            $this->translations->add($translation);
            $translation->setTemplate($this);
        }

        return $this;
    }

    public function removeTranslation(EmailTemplateTranslation $translation): self
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
            $translation->setTemplate(null);
        }

        return $this;
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     *
     * @return EmailTemplate
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     *
     * @return $this
     */
    public function setVisible($visible = true)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Clone template
     */
    public function __clone()
    {
        // cloned entity will be child
        $this->parent = $this->id;
        $this->id = null;
        $this->isSystem = false;
        $this->isEditable = true;

        $originalTranslations = $this->getTranslations();

        $this->translations = new ArrayCollection();
        foreach ($originalTranslations as $translation) {
            $this->addTranslation(clone $translation);
        }
        $this->cloneExtendEntityStorage();
    }

    /**
     * Convert entity to string
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getName();
    }

    /**
     * @param string $content
     *
     * @return array With keys 'content', 'params'
     */
    public static function parseContent($content)
    {
        $params = [];

        $boolParams = ['isSystem', 'isEditable'];
        $templateParams = ['name', 'subject', 'entityName', 'isSystem', 'isEditable'];
        foreach ($templateParams as $templateParam) {
            if (preg_match('#@' . $templateParam . '\s?=\s?(.*)\n#i', $content, $match)) {
                $val = trim($match[1]);
                if (isset($boolParams[$templateParam])) {
                    $val = (bool)$val;
                }
                $params[$templateParam] = $val;
                $content = trim(str_replace($match[0], '', $content));
            }
        }

        return [
            'content' => $content,
            'params' => $params,
        ];
    }
}
