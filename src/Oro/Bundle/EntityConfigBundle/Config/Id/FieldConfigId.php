<?php

namespace Oro\Bundle\EntityConfigBundle\Config\Id;

class FieldConfigId implements ConfigIdInterface
{
    /** @var string */
    private $scope;

    /** @var string */
    private $className;

    /** @var string */
    private $fieldName;

    /** @var string|null */
    private $fieldType;

    /**
     * @param string      $scope
     * @param string      $className
     * @param string      $fieldName
     * @param string|null $fieldType
     */
    public function __construct($scope, $className, $fieldName, $fieldType = null)
    {
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
     * @return string|null
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @param string $fieldType
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return sprintf('field_%s_%s_%s', $this->scope, str_replace('\\', '-', $this->className), $this->fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->className, $this->scope, $this->fieldName, $this->fieldType]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->className, $this->scope, $this->fieldName, $this->fieldType) = unserialize($serialized);
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
