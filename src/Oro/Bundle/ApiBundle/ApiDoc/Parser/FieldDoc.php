<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Parser;

class FieldDoc implements \Serializable
{
    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $dataType;

    /** @var boolean */
    protected $required = false;

    /** @var string */
    protected $default = '';

    /** @var string */
    protected $description = '';

    /** @var boolean */
    protected $readonly = false;

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @param string $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param boolean $readonly
     */
    public function setReadonly($readonly)
    {
        $this->readonly = $readonly;
    }

    /**
     * Returns array representation of data.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'dataType' => $this->dataType,
            'required' => $this->required,
            'default' => $this->default,
            'description' => $this->description,
            'readonly' => $this->readonly
        ];
    }

    /**
     * Implementation of \Serializable.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->fieldName,
            $this->dataType,
            $this->required,
            $this->default,
            $this->description,
            $this->readonly
        ]);
    }

    /**
     * Implementation of \Serializable.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->fieldName,
            $this->dataType,
            $this->required,
            $this->default,
            $this->description,
            $this->readonly
            ) = unserialize($serialized);
    }
}
