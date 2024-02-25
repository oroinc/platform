<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
* Entity that represents Config Model Index Value
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_entity_config_index_value')]
#[ORM\Index(columns: ['scope', 'code', 'value', 'entity_id'], name: 'idx_entity_config_index_entity')]
#[ORM\Index(columns: ['scope', 'code', 'value', 'field_id'], name: 'idx_entity_config_index_field')]
class ConfigModelIndexValue
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EntityConfigModel::class, inversedBy: 'indexedValues')]
    #[ORM\JoinColumn(name: 'entity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EntityConfigModel $entity = null;

    #[ORM\ManyToOne(targetEntity: FieldConfigModel::class, inversedBy: 'indexedValues')]
    #[ORM\JoinColumn(name: 'field_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?FieldConfigModel $field = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255)]
    protected ?string $code = null;

    #[ORM\Column(name: 'scope', type: Types::STRING, length: 255)]
    protected ?string $scope = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $value = null;

    /**
     * @param string|null $scope
     * @param string|null $code
     * @param string|null $value
     */
    public function __construct($scope = null, $code = null, $value = null)
    {
        $this->scope = $scope;
        $this->code  = $code;
        $this->value = $value;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return ConfigModelIndexValue
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set scope
     *
     * @param string $scope
     * @return ConfigModelIndexValue
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return ConfigModelIndexValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set owning entity
     *
     * @param EntityConfigModel $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get owning entity
     *
     * @return EntityConfigModel
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set owning field
     *
     * @param FieldConfigModel $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get owning field
     *
     * @return FieldConfigModel
     */
    public function getField()
    {
        return $this->field;
    }
}
