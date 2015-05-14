<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Annotation\SerializedName;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataAuditBundle\Entity\Repository\AuditRepository")
 * @ORM\Table(name="oro_audit", indexes={
 *  @Index(name="idx_oro_audit_logged_at", columns={"logged_at"})
 * })
 */
class Audit extends AbstractLogEntry
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
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
     * @Soap\ComplexType("dateTime", nillable=true)
     */
    protected $loggedAt;

    /**
     * @var string $objectId
     *
     * @ORM\Column(name="object_id", type="integer", length=32, nullable=true)
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $objectId;

    /**
     * @var string $objectClass
     *
     * @ORM\Column(name="object_class", type="string", length=255)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $objectClass;

    /**
     * @var string $objectName
     *
     * @ORM\Column(name="object_name", type="string", length=255)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $objectName;

    /**
     * @var integer $version
     *
     * @ORM\Column(type="integer")
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $version;

    protected $data;

    /**
     * @var AuditField[]|Collection
     *
     * @ORM\OneToMany(targetEntity="AuditField", mappedBy="audit", cascade={"persist"})
     */
    protected $fields;

    /**
     * @var string $username
     *
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $username;

    /**
     * @var User $user
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Type("string")
     * @SerializedName("username")
     */
    protected $user;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    /**
     * Set user
     *
     * @param  User  $user
     * @return Audit
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get user name
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getUser() ? $this->getUser()->getUsername() : '';
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
     * @param  string $objectName
     * @return Audit
     */
    public function setObjectName($objectName)
    {
        $this->objectName = $objectName;

        return $this;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     * @return User
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
     * Get fields
     *
     * @return AuditField[]|Collection
     */
    public function getFields()
    {
        if ($this->fields === null) {
            $this->fields = new ArrayCollection();
        }

        return $this->fields;
    }

    /**
     * Get field
     *
     * @return AuditField[]|Collection|false
     */
    public function getField($field)
    {
        return $this->fields->filter(function (AuditField $auditField) use ($field) {
            return $auditField->getField() === $field;
        })->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getFields() as $field) {
            $newValue = $field->getNewValue();
            $oldValue = $field->getOldValue();
            if (in_array($field->getDataType(), ['date', 'datetime'])) {
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
     * @throws BadMethodCallException
     */
    public function setData($data)
    {
        throw new \BadMethodCallException('Method "setData" is not supported. Use "createField" method instead.');
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

        if ($existingField = $this->getField($field)) {
            $this->fields->removeElement($existingField);
        }

        $auditField = new AuditField($this, $field, $dataType, $newValue, $oldValue);
        $this->fields->add($auditField);

        return $this;
    }
}
