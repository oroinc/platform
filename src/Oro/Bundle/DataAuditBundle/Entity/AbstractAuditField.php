<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use LogicException;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

/**
 * @ORM\MappedSuperclass
 */
abstract class AbstractAuditField
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AbstractAudit
     *
     * @ORM\ManyToOne(targetEntity="AbstractAudit", inversedBy="fields", cascade={"persist"})
     * @ORM\JoinColumn(name="audit_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $audit;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=false)
     */
    protected $field;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default"=true})
     */
    protected $visible = true;

    /**
     * @var string
     *
     * @ORM\Column(name="data_type", type="string", nullable=false)
     */
    protected $dataType;

    /**
     * @var int
     *
     * @ORM\Column(name="old_integer", type="bigint", nullable=true)
     */
    protected $oldInteger;

    /**
     * @var float
     *
     * @ORM\Column(name="old_float", type="float", nullable=true)
     */
    protected $oldFloat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="old_boolean", type="boolean", nullable=true)
     */
    protected $oldBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="old_text", type="text", nullable=true)
     */
    protected $oldText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_date", type="date", nullable=true)
     */
    protected $oldDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_time", type="time", nullable=true)
     */
    protected $oldTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetime", type="datetime", nullable=true)
     */
    protected $oldDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_datetimetz", type="datetimetz", nullable=true)
     */
    protected $oldDatetimetz;

    /**
     * @var object
     *
     * @ORM\Column(name="old_object", type="object", nullable=true)
     */
    protected $oldObject;

    /**
     * @var array
     *
     * @ORM\Column(name="old_array", type="array", nullable=true)
     */
    protected $oldArray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_simplearray", type="simple_array", nullable=true)
     */
    protected $oldSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="old_jsonarray", type="json_array", nullable=true)
     */
    protected $oldJsonarray;

    /**
     * @var int
     *
     * @ORM\Column(name="new_integer", type="bigint", nullable=true)
     */
    protected $newInteger;

    /**
     * @var int
     *
     * @ORM\Column(name="new_float", type="float", nullable=true)
     */
    protected $newFloat;

    /**
     * @var bool
     *
     * @ORM\Column(name="new_boolean", type="boolean", nullable=true)
     */
    protected $newBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="new_text", type="text", nullable=true)
     */
    protected $newText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_date", type="date", nullable=true)
     */
    protected $newDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_time", type="time", nullable=true)
     */
    protected $newTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetime", type="datetime", nullable=true)
     */
    protected $newDatetime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_datetimetz", type="datetimetz", nullable=true)
     */
    protected $newDatetimetz;

    /**
     * @var object
     *
     * @ORM\Column(name="new_object", type="object", nullable=true)
     */
    protected $newObject;

    /**
     * @var array
     *
     * @ORM\Column(name="new_array", type="array", nullable=true)
     */
    protected $newArray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_simplearray", type="simple_array", nullable=true)
     */
    protected $newSimplearray;

    /**
     * @var array
     *
     * @ORM\Column(name="new_jsonarray", type="json_array", nullable=true)
     */
    protected $newJsonarray;

    /**
     * @param AbstractAudit $audit
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct(AbstractAudit $audit, $field, $dataType, $newValue, $oldValue)
    {
        $this->audit = $audit;
        $this->field = $field;
        $this->dataType = AuditFieldTypeRegistry::getAuditType($dataType);

        $this->setOldValue($oldValue);
        $this->setNewValue($newValue);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @return AbstractAudit
     */
    public function getAudit()
    {
        return $this->audit;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        $propertyName = $this->getPropertyName('new');

        return $this->$propertyName;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        $propertyName = $this->getPropertyName('old');

        return $this->$propertyName;
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param mixed $value
     *
     * @return AuditField
     */
    protected function setOldValue($value)
    {
        $propertyValue = $this->getPropertyName('old');
        $this->$propertyValue = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return AuditField
     */
    protected function setNewValue($value)
    {
        $propertyValue = $this->getPropertyName('new');
        $this->$propertyValue = $value;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    protected function getPropertyName($type)
    {
        $name = sprintf('%s%s', $type, ucfirst($this->dataType));
        if (property_exists(get_class($this), $name)) {
            return $name;
        }

        $customName = sprintf('%s_%s', $type, $this->dataType);
        if (property_exists(get_class($this), $customName)) {
            return $customName;
        }

        throw new LogicException(sprintf(
            'Neither property "%s" nor "%s" was found. Maybe you forget to add migration?',
            $name,
            $customName
        ));
    }
}
