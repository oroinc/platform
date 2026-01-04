<?php

namespace Oro\Bundle\EntityBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Represents a field that value can be a scalar, an array
 * or it can retrieved from another source if it does not have own value.
 */
#[ORM\Entity(repositoryClass: EntityFieldFallbackValueRepository::class)]
#[ORM\Table(name: 'oro_entity_fallback_value')]
#[Config]
class EntityFieldFallbackValue
{
    public const FALLBACK_TYPE = 'fallbackType';
    public const FALLBACK_LIST = 'fallbackList';
    public const FALLBACK_SCALAR_FIELD = 'scalarValue';
    public const FALLBACK_ARRAY_FIELD = 'arrayValue';
    public const FALLBACK_PARENT_FIELD = 'fallback';

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'fallback', type: Types::STRING, length: 64, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?string $fallback = null;

    /**
     * @var mixed
     */
    #[ORM\Column(name: 'scalar_value', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $scalarValue;

    /**
     * @var array
     */
    #[ORM\Column(name: 'array_value', type: Types::ARRAY, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected $arrayValue;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScalarValue()
    {
        return $this->scalarValue;
    }

    /**
     * @param mixed $scalarValue
     * @return $this
     */
    public function setScalarValue($scalarValue)
    {
        $this->scalarValue = $scalarValue;

        return $this;
    }

    /**
     * @return array
     */
    public function getArrayValue()
    {
        return $this->arrayValue;
    }

    /**
     * @param array $arrayValue
     * @return $this
     */
    public function setArrayValue($arrayValue)
    {
        $this->arrayValue = $arrayValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOwnValue()
    {
        if (!is_null($this->scalarValue)) {
            return $this->scalarValue;
        }

        return $this->arrayValue;
    }

    public function __clone()
    {
        $this->id = null;
    }
}
