<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;

/**
 * Represents all(extended included) fields and relations for each Entity
 */
#[ORM\Entity(repositoryClass: FieldConfigModelRepository::class)]
#[ORM\Table(name: 'oro_entity_config_field')]
class FieldConfigModel extends ConfigModel
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EntityConfigModel::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'entity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EntityConfigModel $entity = null;

    /**
     * IMPORTANT: do not modify this collection manually. addToIndex and removeFromIndex should be used
     *
     * @var Collection<int, ConfigModelIndexValue>
     */
    #[ORM\OneToMany(mappedBy: 'field', targetEntity: ConfigModelIndexValue::class, cascade: ['all'])]
    protected ?Collection $indexedValues = null;

    #[ORM\Column(name: 'field_name', type: Types::STRING, length: 255)]
    protected ?string $fieldName = null;

    #[ORM\Column(type: Types::STRING, length: 60, nullable: false)]
    protected ?string $type = null;

    protected $options;

    /**
     * @param string|null $fieldName
     * @param string|null $type
     */
    public function __construct($fieldName = null, $type = null)
    {
        $this->fieldName     = $fieldName;
        $this->type          = $type;
        $this->mode          = self::MODE_DEFAULT;
        $this->indexedValues = new ArrayCollection();
        $this->options       = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param EntityConfigModel $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return EntityConfigModel
     */
    public function getEntity()
    {
        return $this->entity;
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
        $result->setField($this);

        return $result;
    }
}
