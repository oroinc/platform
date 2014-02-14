<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Id;

class FieldConfigId implements ConfigIdInterface
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $fieldType;

    public function __construct($scope, $className, $fieldName, $fieldType = null)
    {
        if (empty($scope)) {
            throw new \InvalidArgumentException('$scope must not be empty');
        }
        if (empty($className)) {
            throw new \InvalidArgumentException('$className must not be empty');
        }
        if (empty($fieldName)) {
            throw new \InvalidArgumentException('$fieldName must not be empty');
        }

        $this->scope     = $scope;
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->fieldType = $fieldType;
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
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param string $fieldType
     * @return $this
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return sprintf('field_%s_%s_%s', $this->scope, strtr($this->className, '\\', '-'), $this->fieldName);
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
                $this->fieldName,
                $this->fieldType,
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
            $this->fieldName,
            $this->fieldType,
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return FieldConfigId A new instance of a FieldConfigId object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new FieldConfigId(
            $data['scope'],
            $data['className'],
            $data['fieldName'],
            $data['fieldType']
        );
    }
    // @codingStandardsIgnoreEnd
}
