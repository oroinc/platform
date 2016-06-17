<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use JMS\Serializer\Annotation as JMS;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EmailBundle\Model\EmailTemplateInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * EmailTemplate
 *
 * @ORM\Table(name="oro_email_template",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="UQ_NAME", columns={"name", "entityName"})},
 *      indexes={@ORM\Index(name="email_name_idx", columns={"name"}),
 * @ORM\Index(name="email_is_system_idx", columns={"isSystem"}),
 * @ORM\Index(name="email_entity_name_idx", columns={"entityName"})})
 * @ORM\Entity(repositoryClass="Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository")
 * @Gedmo\TranslationEntity(class="Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "category"="email"
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @JMS\ExclusionPolicy("ALL")
 */
class EmailTemplate implements EmailTemplateInterface, Translatable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isSystem", type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Expose
     */
    protected $isSystem;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isEditable", type="boolean")
     * @JMS\Type("boolean")
     * @JMS\Expose
     */
    protected $isEditable;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $name;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent", type="integer", nullable=true)
     * @JMS\Type("integer")
     * @JMS\Expose
     */
    protected $parent;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     * @Gedmo\Translatable
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true)
     * @Gedmo\Translatable
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $content;

    /**
     * @var string
     *
     * @ORM\Column(name="entityName", type="string", length=255, nullable=true)
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $entityName;

    /**
     * Template type:
     *  - html
     *  - text
     *
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=20)
     * @JMS\Type("string")
     * @JMS\Expose
     */
    protected $type = 'html';

    /**
     * @var string
     *
     * @Gedmo\Locale()
     */
    protected $locale;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation",
     *     mappedBy="object",
     *     cascade={"persist", "remove"}
     * )
     * @Assert\Valid(deep = true)
     */
    protected $translations;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @ORM\Column(type="boolean", options={"default"=true})
     * @var bool
     */
    protected $visible = true;

    /**
     * @param        $name
     * @param string $content
     * @param string $type
     * @param bool   $isSystem
     */
    public function __construct($name = '', $content = '', $type = 'html', $isSystem = false)
    {
        // name can be overridden from email template
        $this->name = $name;
        // isSystem can be overridden from email template
        $this->isSystem = $isSystem;
        // isEditable can be overridden from email template
        $this->isEditable = false;

        $boolParams     = array('isSystem', 'isEditable');
        $templateParams = array('name', 'subject', 'entityName', 'isSystem', 'isEditable');
        foreach ($templateParams as $templateParam) {
            if (preg_match('#@' . $templateParam . '\s?=\s?(.*)\n#i', $content, $match)) {
                $val = trim($match[1]);
                if (isset($boolParams[$templateParam])) {
                    $val = (bool)$val;
                }
                $this->$templateParam = $val;
                $content              = trim(str_replace($match[0], '', $content));
            }
        }

        // make sure that user's template is editable
        if (!$this->isSystem && !$this->isEditable) {
            $this->isEditable = true;
        }

        $this->type         = $type;
        $this->content      = $content;
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
     * @return EmailTemplate
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
     * Set locale
     *
     * @param string $locale
     *
     * @return EmailTemplate
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Set translations
     *
     * @param ArrayCollection $translations
     *
     * @return EmailTemplate
     */
    public function setTranslations($translations)
    {
        /** @var EmailTemplateTranslation $translation */
        foreach ($translations as $translation) {
            $translation->setObject($this);
        }

        $this->translations = $translations;

        return $this;
    }

    /**
     * Get translations
     *
     * @return ArrayCollection|EmailTemplateTranslation[]
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
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
        $this->parent     = $this->id;
        $this->id         = null;
        $this->isSystem   = false;
        $this->isEditable = true;

        if ($this->getTranslations() instanceof ArrayCollection) {
            $clonedTranslations = new ArrayCollection();
            foreach ($this->getTranslations() as $translation) {
                $clonedTranslations->add(clone $translation);
            }
            $this->setTranslations($clonedTranslations);
        }
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
}
