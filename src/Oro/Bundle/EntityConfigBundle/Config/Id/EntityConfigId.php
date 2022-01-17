<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Id;

/**
 * Stores data config identification entity.
 */
class EntityConfigId implements ConfigIdInterface
{
    /** @var string */
    private $scope;

    /** @var string */
    private $className;

    /**
     * @param string      $scope
     * @param string|null $className
     */
    public function __construct($scope, $className = null)
    {
        $this->scope     = $scope;
        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return sprintf('entity_%s_%s', $this->scope, str_replace('\\', '-', $this->className));
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->className, $this->scope]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        [$this->className, $this->scope] = unserialize($serialized);
    }

    public function __serialize(): array
    {
        return [$this->className, $this->scope];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->className, $this->scope] = $serialized;
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return EntityConfigId A new instance of a EntityConfigId object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new EntityConfigId($data['scope'], $data['className']);
    }
    // @codingStandardsIgnoreEnd
}
