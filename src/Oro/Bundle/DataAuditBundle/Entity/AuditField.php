<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\DataAuditBundle\Exception\UnsupportedDataTypeException;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_audit_field")
 */
class AuditField
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
     * @var Audit
     *
     * @ORM\ManyToOne(targetEntity="Audit", inversedBy="fields", cascade={"persist"})
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
     * @var string
     *
     * @ORM\Column(name="data_type", type="string", nullable=false)
     */
    protected $dataType;

    /**
     * @var int
     *
     * @ORM\Column(name="old_integer", type="bigint")
     */
    protected $oldInteger;

    /**
     * @var float
     *
     * @ORM\Column(name="old_float", type="float")
     */
    protected $oldFloat;

    /**
     * @var boolean
     *
     * @ORM\Column(name="old_boolean", type="boolean")
     */
    protected $oldBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="old_text", type="text")
     */
    protected $oldText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_date", type="date")
     */
    protected $oldDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_time", type="time")
     */
    protected $oldTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_date_time", type="datetime")
     */
    protected $oldDatetime;

    /**
     * @var int
     *
     * @ORM\Column(name="new_integer", type="bigint")
     */
    protected $newInteger;

    /**
     * @var int
     *
     * @ORM\Column(name="new_float", type="float")
     */
    protected $newFloat;

    /**
     * @var bool
     *
     * @ORM\Column(name="new_boolean", type="boolean")
     */
    protected $newBoolean;

    /**
     * @var string
     *
     * @ORM\Column(name="new_text", type="text")
     */
    protected $newText;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_date", type="date")
     */
    protected $newDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_time", type="time")
     */
    protected $newTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_date_time", type="datetime")
     */
    protected $newDatetime;

    /**
     * @param Audit $audit
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct(Audit $audit, $field, $dataType, $newValue, $oldValue)
    {
        $this->audit = $audit;
        $this->field = $field;

        $this->dataType = $this->normalizeDataTypeName($dataType);
        if (is_null($this->dataType)) {
            throw new UnsupportedDataTypeException($dataType);
        }

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
     * @return Audit
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
     */
    private function setOldValue($value)
    {
        $propertyValue = $this->getPropertyName('old');
        $this->$propertyValue = $value;
    }

    /**
     * @param mixed $value
     */
    private function setNewValue($value)
    {
        $propertyValue = $this->getPropertyName('new');
        $this->$propertyValue = $value;
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getPropertyName($type)
    {
        return sprintf('%s%s', $type, ucfirst($this->dataType));
    }

    /**
     * @param string $dataType
     *
     * @return string|null
     */
    private function normalizeDataTypeName($dataType)
    {
        switch ($dataType) {
            case 'boolean':
                return 'boolean';
            case 'text':
            case 'string':
            case 'guid':
                return 'text';
            case 'smallint':
            case 'integer':
                return 'integer';
            case 'decimal':
            case 'float';
                return 'float';
            case 'date':
                return 'date';
            case 'time':
                return 'time';
            case 'datetime':
                return 'datetime';
            default:
                return null;
        }
    }
}
