<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\Impersonation;

/**
 * Abstract class for audit entities
 *
 *
 */
#[ORM\Entity(repositoryClass: AuditRepository::class)]
#[ORM\Table(name: 'oro_audit')]
#[ORM\Index(columns: ['logged_at'], name: 'idx_oro_audit_logged_at')]
#[ORM\Index(columns: ['type'], name: 'idx_oro_audit_type')]
#[ORM\Index(columns: ['object_class'], name: 'idx_oro_audit_object_class')]
#[ORM\Index(columns: ['object_id', 'object_class', 'type'], name: 'idx_oro_audit_obj_by_type')]
#[ORM\Index(columns: ['owner_description'], name: 'idx_oro_audit_owner_descr')]
#[ORM\UniqueConstraint(name: 'idx_oro_audit_version', columns: ['object_id', 'object_class', 'version', 'type'])]
#[ORM\UniqueConstraint(
    name: 'idx_oro_audit_transaction',
    columns: ['object_id', 'object_class', 'transaction_id', 'type']
)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 30)]
#[ORM\DiscriminatorMap(['audit' => Audit::class])]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'organization',
            'owner_column_name' => 'organization_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'account_management', 'permissions' => 'VIEW']
    ]
)]
abstract class AbstractAudit
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    const OBJECT_NAME_MAX_LENGTH = 255;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: true)]
    protected ?string $action = null;

    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $loggedAt = null;

    #[ORM\Column(name: 'object_id', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $objectId = null;

    #[ORM\Column(name: 'object_class', type: Types::STRING, length: 255)]
    protected ?string $objectClass = null;

    #[ORM\Column(name: 'object_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $objectName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    protected ?int $version = null;

    /**
     * @var Collection<int, AuditField>
     */
    #[ORM\OneToMany(mappedBy: 'audit', targetEntity: AuditField::class, cascade: ['persist'], orphanRemoval: true)]
    protected ?Collection $fields = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\ManyToOne(targetEntity: Impersonation::class)]
    #[ORM\JoinColumn(name: 'impersonation_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Impersonation $impersonation = null;

    #[ORM\Column(name: 'transaction_id', type: Types::STRING, length: 36)]
    protected ?string $transactionId = null;

    #[ORM\Column(name: 'owner_description', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $ownerDescription = null;

    /**
     * @var array $additionalFields
     */
    #[ORM\Column(name: 'additional_fields', type: Types::ARRAY, nullable: true)]
    protected $additionalFields = [];

    /**
     * Set user
     *
     * @param AbstractUser|null $user
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
     * @param Organization|null $organization
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
     * @param Impersonation|null $impersonation
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
        $this->objectId = (string) $objectId;
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

    public function setAdditionalFields(array $additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }
}
