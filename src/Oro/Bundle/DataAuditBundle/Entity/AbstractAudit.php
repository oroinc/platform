<?php
namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Model\FieldsTransformer;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Impersonation;

/**
 * Abstract class for audit entities
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository")
 * @ORM\Table(
 *     name="oro_audit",
 *     indexes={
 *         @ORM\Index(name="idx_oro_audit_logged_at", columns={"logged_at"}),
 *         @ORM\Index(name="idx_oro_audit_type", columns={"type"}),
 *         @ORM\Index(name="idx_oro_audit_object_class", columns={"object_class"}),
 *         @ORM\Index(name="idx_oro_audit_obj_by_type", columns={"object_id", "object_class", "type"}),
 *         @ORM\Index(name="idx_oro_audit_owner_descr", columns={"owner_description"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_oro_audit_version", columns={"object_id", "object_class", "version"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"audit" = "Oro\Bundle\DataAuditBundle\Entity\Audit"})
 *
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="account_management",
 *              "permissions"="VIEW"
 *          }
 *     }
 * )
 */
abstract class AbstractAudit
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    const OBJECT_NAME_MAX_LENGTH = 255;

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @var string $action
     *
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    protected $action;

    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="datetime", nullable=true)
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     */
    protected $objectClass;

    /**
     * @var string $objectName
     *
     * @ORM\Column(name="object_name", type="string", length=255, nullable=true)
     */
    protected $objectName;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $version;

    /**
     * @var AbstractAuditField[]|Collection
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\DataAuditBundle\Entity\AuditField",
     *     mappedBy="audit",
     *     cascade={"persist"}
     * )
     */
    protected $fields;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @var Impersonation $impersonation
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\Impersonation")
     * @ORM\JoinColumn(name="impersonation_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $impersonation;

    /**
     * @var string $transactionId
     *
     * @ORM\Column(name="transaction_id", type="string", length=255)
     */
    protected $transactionId;

    /**
     * @var string
     *
     * @ORM\Column(name="owner_description", type="string", length=255, nullable=true)
     */
    protected $ownerDescription;

    /**
     * @var array $additionalFields
     *
     * @ORM\Column(name="additional_fields", type="array", nullable=true)
     */
    protected $additionalFields = [];

    /**
     * Set user
     *
     * @param  AbstractUser $user
     * @return AbstractAudit
     */
    abstract public function setUser(AbstractUser $user = null);

    /**
     * Get user
     *
     * @return AbstractUser|null
     */
    abstract public function getUser();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
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
     * @param AuditField $field
     *
     * @return $this
     */
    public function addField(AuditField $field)
    {
        $existingField = $this->getField($field->getField());
        if ($existingField) {
            $this->getFields()->removeElement($existingField);
        }
        
        $this->getFields()->add($field);
        $field->setAudit($this);

        return $this;
    }

    /**
     * @return AbstractAuditField[]|Collection
     */
    public function getFields()
    {
        if (false == $this->fields) {
            $this->fields = new ArrayCollection();
        }

        return $this->fields;
    }

    /**
     * Get field
     *
     * @param string $field
     *
     * @return AbstractAuditField|false
     */
    public function getField($field)
    {
        return $this->getFields()->filter(function (AbstractAuditField $auditField) use ($field) {
            return $auditField->getField() === $field;
        })->first();
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return AbstractAudit
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
     * @param Impersonation $impersonation
     * @return $this
     */
    public function setImpersonation(Impersonation $impersonation = null)
    {
        $this->impersonation = $impersonation;

        return $this;
    }

    /**
     * @return Impersonation
     */
    public function getImpersonation()
    {
        return $this->impersonation;
    }

    /**
     * Get object name
     *
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * Set object name
     *
     * @param string $objectName
     * @return AbstractAudit
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * @param \DateTime|null $loggedAt
     */
    public function setLoggedAt(\DateTime $loggedAt = null)
    {
        $this->loggedAt = $loggedAt ?: new \DateTime();
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set action
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Get object class
     *
     * @return string
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set object class
     *
     * @param string $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = $objectClass;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set object id
     *
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * Set current version
     *
     * @param integer $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get current version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getOwnerDescription()
    {
        return $this->ownerDescription;
    }

    /**
     * @param string $ownerDescription
     */
    public function setOwnerDescription($ownerDescription)
    {
        $this->ownerDescription = mb_substr($ownerDescription, 0, 255, mb_detect_encoding($ownerDescription));
    }

    /**
     * @return array|null
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }

    /**
     * @param array $additionalFields
     */
    public function setAdditionalFields(array $additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }
}
