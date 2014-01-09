<?php

namespace Oro\Bundle\ConfigBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConfigValue
 *
 * @ORM\Table(
 *  name="oro_config_value",
 *  uniqueConstraints={@ORM\UniqueConstraint(name="CONFIG_VALUE_UQ_ENTITY", columns={"name", "section", "config_id"})}
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ConfigValue
{
    const FIELD_SCALAR_TYPE = 'scalar';
    const FIELD_OBJECT_TYPE = 'object';
    const FIELD_ARRAY_TYPE  = 'array';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var Config[]
     *
     * @ORM\ManyToOne(targetEntity="Config", inversedBy="values")
     * @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     */
    protected $config;

    /**
     * @var string
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $section;

    /**
     * @var string
     * @ORM\Column(name="text_value", type="text", nullable=true)
     */
    protected $textValue;

    /**
     * @var string
     * @ORM\Column(name="object_value", type="object", nullable=true)
     */
    protected $objectValue;

    /**
     * @var string
     * @ORM\Column(name="array_value", type="array", nullable=true)
     */
    protected $arrayValue;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=false)
     */
    protected $type = self::FIELD_SCALAR_TYPE;

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
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set config
     *
     * @param string $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     *
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->clearValue();
        switch (true) {
            case is_object($value):
                $this->objectValue = $value;
                $this->type        = self::FIELD_OBJECT_TYPE;
                break;
            case is_array($value):
                $this->arrayValue = $value;
                $this->type       = self::FIELD_ARRAY_TYPE;
                break;
            default:
                $this->textValue = $value;
                $this->type      = self::FIELD_SCALAR_TYPE;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        switch ($this->type) {
            case self::FIELD_ARRAY_TYPE:
                return $this->arrayValue;
                break;
            case self::FIELD_OBJECT_TYPE:
                return $this->objectValue;
                break;
            default:
                return $this->textValue;
        }
    }

    /**
     * @param string $section
     *
     * @return $this
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param string $type
     *
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
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * Clear all value types
     *
     * @return void
     */
    protected function clearValue()
    {
        $this->objectValue = $this->arrayValue = $this->textValue = null;
    }
}
