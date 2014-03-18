<?php

namespace Oro\Bundle\EntityConfigBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="oro_entity_config_index_value")
 * @ORM\Entity
 */
class ConfigModelIndexValue
{
    const ENTITY_NAME = 'OroEntityConfigBundle:ConfigModelIndexValue';

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var EntityConfigModel
     * @ORM\ManyToOne(targetEntity="EntityConfigModel", inversedBy="indexedValues", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
     * })
     */
    protected $entity;

    /**
     * @var FieldConfigModel
     * @ORM\ManyToOne(targetEntity="FieldConfigModel", inversedBy="indexedValues", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     * })
     */
    protected $field;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=255)
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="scope", type="string", length=255)
     */
    protected $scope;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $value;

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
