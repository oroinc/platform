<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_entity_config",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="oro_entity_config_uq", columns={"class_name"})})
 */
class EntityConfigModel extends ConfigModel
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * IMPORTANT: do not modify this collection manually. addToIndex and removeFromIndex should be used
     *
     * @var ArrayCollection|ConfigModelIndexValue[]
     * @ORM\OneToMany(targetEntity="ConfigModelIndexValue", mappedBy="entity", cascade={"all"})
     */
    protected $indexedValues;

    /**
     * @var ArrayCollection|FieldConfigModel[]
     * @ORM\OneToMany(targetEntity="FieldConfigModel", mappedBy="entity")
     */
    protected $fields;

    /**
     * @var string
     * @ORM\Column(name="class_name", type="string", length=255)
     */
    protected $className;

    /**
     * @param string|null $className
     */
    public function __construct($className = null)
    {
        $this->mode          = self::MODE_DEFAULT;
        $this->fields        = new ArrayCollection();
        $this->indexedValues = new ArrayCollection();
        if (!empty($className)) {
            $this->setClassName($className);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($className);
        $this->addToIndex('entity_config', 'module_name', $moduleName);
        $this->addToIndex('entity_config', 'entity_name', $entityName);

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param ArrayCollection $fields
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @param FieldConfigModel $field
     * @return $this
     */
    public function addField($field)
    {
        $field->setEntity($this);
        $this->fields->add($field);

        return $this;
    }

    /**
     * @param  callable $filter function (FieldConfigModel $model)
     * @return ArrayCollection|FieldConfigModel[]
     */
    public function getFields(\Closure $filter = null)
    {
        return $filter ? $this->fields->filter($filter) : $this->fields;
    }

    /**
     * @param $fieldName
     * @return FieldConfigModel
     */
    public function getField($fieldName)
    {
        $fields = $this->getFields(
            function (FieldConfigModel $field) use ($fieldName) {
                return $field->getFieldName() == $fieldName;
            }
        );

        return $fields->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexedValues()
    {
        return $this->indexedValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function createIndexedValue($scope, $code, $value)
    {
        $result = new ConfigModelIndexValue($scope, $code, $value);
        $result->setEntity($this);

        return $result;
    }
}
