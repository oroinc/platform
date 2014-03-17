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
     * @ORM\ManyToOne(targetEntity="EntityConfigModel", inversedBy="values", cascade={"persist"})
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="entity_id", referencedColumnName="id")
     * })
     */
    protected $entity;

    /**
     * @var FieldConfigModel
     * @ORM\ManyToOne(targetEntity="FieldConfigModel", inversedBy="values", cascade={"persist"})
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
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
     * @ORM\Column(name="value" type="string", length=255, nullable=true)
     */
    protected $value;

    public function __construct($code = null, $scope = null, $value = null)
    {
        $this->code         = $code;
        $this->scope        = $scope;

        $this->setValue($value);
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
     * @param string $scope
     * @return ConfigModelIndexValue
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set data
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
     * Get data
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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
     * @param FieldConfigModel $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return FieldConfigModel
     */
    public function getField()
    {
        return $this->field;
    }

    public function toArray()
    {
        return array(
            'code'         => $this->code,
            'scope'        => $this->scope,
            'value'        => $this->value,
        );
    }
}
