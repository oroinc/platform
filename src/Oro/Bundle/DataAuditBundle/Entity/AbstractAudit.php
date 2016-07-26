<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_audit", indexes={
 *      @ORM\Index(name="idx_oro_audit_logged_at", columns={"logged_at"}),
 *      @ORM\Index(name="idx_oro_audit_type", columns={"type"}),
 *      @ORM\Index(name="idx_oro_audit_object_class", columns={"object_class"})
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"audit" = "Oro\Bundle\DataAuditBundle\Entity\Audit"})
 */
abstract class AbstractAudit extends AbstractLogEntry
{
    /**
     * @var string $objectName
     *
     * @ORM\Column(name="object_name", type="string", length=255)
     */
    protected $objectName;

    /**
     * @var int $objectId
     *
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    protected $objectId;

    /**
     * Redefined parent property to remove the column from db
     *
     * @var array
     */
    protected $data;

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
     * @var string $username
     */
    protected $username;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

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
     * @param AbstractAudit $audit
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     * @return AbstractAuditField
     */
    protected function getAuditFieldInstance(AbstractAudit $audit, $field, $dataType, $newValue, $oldValue)
    {
        return new AuditField($audit, $field, $dataType, $newValue, $oldValue);
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    /**
     * Create field
     *
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     * @return Audit
     */
    public function createField($field, $dataType, $newValue, $oldValue)
    {
        if ($this->fields === null) {
            $this->fields = new ArrayCollection();
        }

        $existingField = $this->getField($field);
        if ($existingField) {
            $this->fields->removeElement($existingField);
        }

        $auditField = $this->getAuditFieldInstance($this, $field, $dataType, $newValue, $oldValue);
        $this->fields->add($auditField);

        return $this;
    }

    /**
     * Get fields
     *
     * @return AbstractAuditField[]|Collection
     */
    public function getFields()
    {
        if ($this->fields === null) {
            $this->fields = new ArrayCollection();
        }

        return $this->fields;
    }

    /**
     * Get visible fields
     *
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
        return $this->fields
            ->filter(
                function (AbstractAuditField $auditField) use ($field) {
                    return $auditField->getField() === $field;
                }
            )
            ->first();
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
}
