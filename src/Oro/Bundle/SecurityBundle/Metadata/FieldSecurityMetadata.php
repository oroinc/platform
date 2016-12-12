<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

class FieldSecurityMetadata implements \Serializable
{
    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $label;

    /** @var string|null */
    protected $description;

    /** @var string[] */
    protected $permissions;

    /**
     * @param string      $fieldName
     * @param string      $label
     * @param array       $permissions
     * @param string|null $description
     */
    public function __construct(
        $fieldName = '',
        $label = '',
        $permissions = [],
        $description = null
    ) {
        $this->fieldName = $fieldName;
        $this->label = $label;
        $this->description = $description;
        $this->permissions = $permissions;
    }

    /**
     * Returns field name
     *
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * Returns field label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns field description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns available field permissions
     *
     * @return string[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->fieldName,
                $this->label,
                $this->permissions,
                $this->description
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->fieldName,
            $this->label,
            $this->permissions,
            $this->description
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
     * @return FieldSecurityMetadata A new instance of a FieldSecurityMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new FieldSecurityMetadata();
        $result->fieldName = $data['fieldName'];
        $result->label = $data['label'];
        $result->permissions = $data['permissions'];
        $result->description = $data['description'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
