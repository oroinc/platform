<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * Represents security metadata for a field.
 */
class FieldSecurityMetadata
{
    /** @var string */
    private $fieldName;

    /** @var string */
    private $label;

    /** @var string|null */
    private $description;

    /** @var string[] */
    private $permissions;

    /** @var $string */
    private $alias;

    /** @var bool */
    private $isHidden;

    /**
     * @param string      $fieldName
     * @param string      $label
     * @param array       $permissions
     * @param string|null $description
     * @param string      $alias
     * @param bool        $isHidden
     */
    public function __construct(
        $fieldName = '',
        $label = '',
        $permissions = [],
        $description = null,
        $alias = null,
        $isHidden = false
    ) {
        $this->fieldName = $fieldName;
        $this->label = $label;
        $this->description = $description;
        $this->permissions = $permissions;
        $this->alias = $alias;
        $this->isHidden = $isHidden;
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
     * Gets the field name. If set, the permission check will be performed by it.
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Indicates whether the field should be shown or not on permissions list.
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->isHidden;
    }

    public function __serialize(): array
    {
        return [
            $this->fieldName,
            $this->label,
            $this->permissions,
            $this->description,
            $this->alias,
            $this->isHidden
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->fieldName,
            $this->label,
            $this->permissions,
            $this->description,
            $this->alias,
            $this->isHidden
        ] = $serialized;
    }

    /**
     * @param array $data
     *
     * @return FieldSecurityMetadata
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new FieldSecurityMetadata(
            $data['fieldName'],
            $data['label'],
            $data['permissions'],
            $data['description'],
            $data['alias'],
            $data['isHidden']
        );
    }
    // @codingStandardsIgnoreEnd
}
