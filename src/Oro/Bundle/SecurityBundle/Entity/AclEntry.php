<?php

namespace Oro\Bundle\SecurityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * This entity class intended to allow usage of basic acl_entries table in DQL. The main goal of this approach
 * is possibility to use this entity in AclWalker to determine shared records.
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(
 *      name="acl_entries",
 *      indexes={
 *          @ORM\Index(
 *              name="IDX_46C8B806EA000B103D9AB4A6DF9183C9",
 *              columns={"class_id", "object_identity_id", "security_identity_id"}
 *          ),
 *          @ORM\Index(name="IDX_46C8B806EA000B10", columns={"class_id"}),
 *          @ORM\Index(name="IDX_46C8B8063D9AB4A6", columns={"object_identity_id"}),
 *          @ORM\Index(name="IDX_46C8B806DF9183C9", columns={"security_identity_id"}),
 *      },
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="UNIQ_46C8B806EA000B103D9AB4A64DEF17BCE4289BF4",
 *              columns={"class_id", "object_identity_id", "field_name", "ace_order"}
 *          )
 *      }
 * )
 */
class AclEntry
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\ManyToOne(targetEntity="AclClass")
     * @ORM\JoinColumn(name="class_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $class;

    /**
     * @var AclObjectIdentity
     *
     * @ORM\ManyToOne(targetEntity="AclObjectIdentity")
     * @ORM\JoinColumn(name="object_identity_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $objectIdentity;

    /**
     * @var AclSecurityIdentity
     *
     * @ORM\ManyToOne(targetEntity="AclSecurityIdentity")
     * @ORM\JoinColumn(name="security_identity_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $securityIdentity;

    /**
     * @var string
     *
     * @ORM\Column(name="field_name", type="string", length=50, nullable=true)
     */
    protected $fieldName;

    /**
     * @var int
     *
     * @ORM\Column(name="ace_order", type="smallint", options={"unsigned"=true})
     */
    protected $aceOrder;

    /**
     * @var int
     *
     * @ORM\Column(name="mask", type="integer")
     */
    protected $mask;

    /**
     * @var bool
     *
     * @ORM\Column(name="granting", type="boolean")
     */
    protected $granting;

    /**
     * @var string
     *
     * @ORM\Column(name="granting_strategy", type="string", length=30)
     */
    protected $grantingStrategy;

    /**
     * @var bool
     *
     * @ORM\Column(name="audit_success", type="boolean")
     */
    protected $auditSuccess;

    /**
     * @var bool
     *
     * @ORM\Column(name="audit_failure", type="boolean")
     */
    protected $auditFailure;

    /**
     * The field is added in purpose to gain better performance. In other word, denormalization is added.
     * This field duplicates acl_object_identities.object_identifier field.
     *
     * @var int
     *
     * @ORM\Column(name="record_id", type="bigint", options={"unsigned"=true}, nullable=true)
     */
    protected $recordId;

    /**
     * Gets id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets class
     *
     * @param AclClass $class
     * @return self
     */
    public function setClass(AclClass $class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Gets class
     *
     * @return AclClass
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Sets objectIdentity
     *
     * @param AclObjectIdentity $objectIdentity
     * @return self
     */
    public function setObjectIdentity(AclObjectIdentity $objectIdentity)
    {
        $this->objectIdentity = $objectIdentity;

        return $this;
    }

    /**
     * Gets objectIdentity
     *
     * @return AclObjectIdentity
     */
    public function getObjectIdentity()
    {
        return $this->objectIdentity;
    }

    /**
     * Sets securityIdentity
     *
     * @param AclSecurityIdentity $securityIdentity
     * @return self
     */
    public function setSecurityIdentity(AclSecurityIdentity $securityIdentity)
    {
        $this->securityIdentity = $securityIdentity;

        return $this;
    }

    /**
     * Gets securityIdentity
     *
     * @return AclSecurityIdentity
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * Sets fieldName
     *
     * @param string $fieldName
     * @return self
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * Gets fieldName
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Sets aceOrder
     *
     * @param string $aceOrder
     * @return self
     */
    public function setAceOrder($aceOrder)
    {
        $this->aceOrder = $aceOrder;

        return $this;
    }

    /**
     * Gets aceOrder
     *
     * @return int
     */
    public function getAceOrder()
    {
        return $this->aceOrder;
    }

    /**
     * Sets mask
     *
     * @param int $mask
     * @return self
     */
    public function setMask($mask)
    {
        $this->mask = $mask;

        return $this;
    }

    /**
     * Gets mask
     *
     * @return int
     */
    public function getMask()
    {
        return $this->mask;
    }

    /**
     * Sets granting
     *
     * @param bool $granting
     * @return self
     */
    public function setGranting($granting)
    {
        $this->granting = $granting;

        return $this;
    }

    /**
     * Gets granting
     *
     * @return bool
     */
    public function getGranting()
    {
        return $this->granting;
    }

    /**
     * Sets grantingStrategy
     *
     * @param string $grantingStrategy
     * @return self
     */
    public function setGrantingStrategy($grantingStrategy)
    {
        $this->grantingStrategy = $grantingStrategy;

        return $this;
    }

    /**
     * Gets grantingStrategy
     *
     * @return string
     */
    public function getGrantingStrategy()
    {
        return $this->grantingStrategy;
    }

    /**
     * Sets auditSuccess
     *
     * @param string $auditSuccess
     * @return self
     */
    public function setAuditSuccess($auditSuccess)
    {
        $this->auditSuccess = $auditSuccess;

        return $this;
    }

    /**
     * Gets auditSuccess
     *
     * @return bool
     */
    public function getAuditSuccess()
    {
        return $this->auditSuccess;
    }

    /**
     * Sets auditFailure
     *
     * @param bool $auditFailure
     * @return self
     */
    public function setAuditFailure($auditFailure)
    {
        $this->auditFailure = $auditFailure;

        return $this;
    }

    /**
     * Gets auditFailure
     *
     * @return bool
     */
    public function getAuditFailure()
    {
        return $this->auditFailure;
    }

    /**
     * Sets recordId
     *
     * @param int $recordId
     * @return self
     */
    public function setRecordId($recordId)
    {
        $this->recordId = $recordId;

        return $this;
    }

    /**
     * Gets recordId
     *
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }
}
