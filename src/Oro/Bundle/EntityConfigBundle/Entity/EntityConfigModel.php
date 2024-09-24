<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
* Entity that represents Entity Config Model
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_entity_config')]
#[ORM\UniqueConstraint(name: 'oro_entity_config_uq', columns: ['class_name'])]
class EntityConfigModel extends ConfigModel
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    /**
     * IMPORTANT: do not modify this collection manually. addToIndex and removeFromIndex should be used
     *
     * @var Collection<int, ConfigModelIndexValue>
     */
    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: ConfigModelIndexValue::class, cascade: ['all'])]
    protected ?Collection $indexedValues = null;

    /**
     * @var Collection<int, FieldConfigModel>
     */
    #[ORM\OneToMany(mappedBy: 'entity', targetEntity: FieldConfigModel::class)]
    protected ?Collection $fields = null;

    #[ORM\Column(name: 'class_name', type: Types::STRING, length: 255)]
    protected ?string $className = null;

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
    #[\Override]
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
     * @param \Closure|null $filter function (FieldConfigModel $model)
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

    #[\Override]
    public function getIndexedValues()
    {
        return $this->indexedValues;
    }

    #[\Override]
    protected function createIndexedValue($scope, $code, $value)
    {
        $result = new ConfigModelIndexValue($scope, $code, $value);
        $result->setEntity($this);

        return $result;
    }
}
