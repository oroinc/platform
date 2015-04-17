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
    /** @var string[] */
    protected static $typeMap = [
        'boolean'   => 'boolean',
        'text'      => 'text',
        'string'    => 'text',
        'guid'      => 'text',
        'manyToOne' => 'text',
        'enum'      => 'text',
        'multiEnum' => 'text',
        'ref-many'  => 'text',
        'ref-one'   => 'text',
        'smallint'  => 'integer',
        'integer'   => 'integer',
        'bigint'    => 'integer',
        'decimal'   => 'float',
        'float'     => 'float',
        'money'     => 'float',
        'percent'   => 'float',
        'date'      =>  'date',
        'time'      => 'time',
        'datetime'  => 'datetime',
    ];

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

        $this->dataType = static::normalizeDataTypeName($dataType);
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
     *
     * @return this
     */
    private function setOldValue($value)
    {
        $propertyValue = $this->getPropertyName('old');
        $this->$propertyValue = $value;

        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return this
     */
    private function setNewValue($value)
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
    private function getPropertyName($type)
    {
        return sprintf('%s%s', $type, ucfirst($this->dataType));
    }

    /**
     * @param string $dataType
     *
     * @return string|null
     */
    public static function normalizeDataTypeName($dataType)
    {
        if (isset(static::$typeMap[$dataType])) {
            return static::$typeMap[$dataType];
        }

        return null;
    }
}
