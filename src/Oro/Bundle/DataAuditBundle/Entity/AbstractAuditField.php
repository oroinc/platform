<?php

namespace Oro\Bundle\DataAuditBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Oro\Bundle\DataAuditBundle\Model\AuditFieldTypeRegistry;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractAuditField
{
    use BitFieldTypeTrait;
    use NumericFieldTypeTrait;
    use StringFieldTypeTrait;
    use DateTimeFieldType;
    use ArrayFieldTypeTrait;
    use ObjectFieldTypeTrait;
    use CollectionTypeTrait;
    
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
     * @var string
     *
     * @ORM\Column(name="translation_domain", type="string", length=100, nullable=true)
     */
    protected $translationDomain;

    /**
     * @param string $field
     * @param string $dataType
     * @param mixed $newValue
     * @param mixed $oldValue
     */
    public function __construct($field, $dataType, $newValue, $oldValue)
    {
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
     * @param AbstractAudit $audit
     */
    public function setAudit(AbstractAudit $audit)
    {
        $this->audit = $audit;
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
     * @return string|null
     */
    public function getTranslationDomain()
    {
        return $this->translationDomain;
    }

    /**
     * @param string $translationDomain
     * @return $this
     */
    public function setTranslationDomain(string $translationDomain)
    {
        $this->translationDomain = $translationDomain;

        return $this;
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
