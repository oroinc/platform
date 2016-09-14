<?php
namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository")
 * @ORM\Table(
 *     name="oro_audit",
 *     indexes={
 *         @ORM\Index(name="idx_oro_audit_logged_at", columns={"logged_at"}),
 *         @ORM\Index(name="idx_oro_audit_type", columns={"type"}),
 *         @ORM\Index(name="idx_oro_audit_object_class", columns={"object_class"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_oro_audit_version", columns={"object_id", "object_class", "version"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"audit" = "Oro\Bundle\DataAuditBundle\Entity\Audit"})
 */
abstract class AbstractAudit
{
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

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
     * @ORM\Column(type="string", length=8)
     */
    protected $action;

    /**
     * @var string $loggedAt
     *
     * @ORM\Column(name="logged_at", type="datetime")
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", length=64, nullable=true)
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
     * @ORM\Column(name="object_name", type="string", length=255)
     */
    protected $objectName;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer")
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
     * @var string $transactionId
     *
     * @ORM\Column(name="transaction_id", type="string", length=255)
     */
    protected $transactionId;

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
     * @return AbstractAuditField[]|Collection
     */
    protected function getVisibleFields()
    {
        return $this->getFields()->filter(function (AbstractAuditField $field) {
            return $field->isVisible();
        });
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
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getVisibleFields() as $field) {
            $newValue = $field->getNewValue();
            $oldValue = $field->getOldValue();
            if (in_array($field->getDataType(), ['date', 'datetime', 'array', 'jsonarray'], true)) {
                $newValue = [
                    'value' => $newValue,
                    'type'  => $field->getDataType(),
                ];
                $oldValue = [
                    'value' => $oldValue,
                    'type'  => $field->getDataType(),
                ];
            }
            $data[$field->getField()] = [
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }
        return $data;
    }
}
