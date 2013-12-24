<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Id;

class EntityConfigId implements ConfigIdInterface
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $className;

    public function __construct($className, $scope)
    {
        $this->className = $className;
        $this->scope     = $scope;
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
        return sprintf('entity_%s_%s', $this->scope, strtr($this->className, '\\', '-'));
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->className,
                $this->scope,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->className,
            $this->scope,
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return EntityConfigId A new instance of a EntityConfigId object
     */
    public static function __set_state($data)
    {
        return new EntityConfigId($data['className'], $data['scope']);
    }
}
