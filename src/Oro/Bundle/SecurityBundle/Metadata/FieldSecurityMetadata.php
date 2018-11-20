<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * Model that contains security metadata for field by security type
 */
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
     * Field name. If set, the permission check will be performed by it.
     *
     * @var $string
     */
    protected $alias;

    /**
     * Determinate if field should be shown or not on permissions list.
     *
     * @var bool
     */
    protected $isHidden;

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
        $this->alias    = $alias;
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
     * @param string $label
     * @return FieldSecurityMetadata
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
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
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->isHidden;
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
                $this->description,
                $this->alias,
                $this->isHidden
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
            $this->description,
            $this->alias,
            $this->isHidden
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
        $result->alias = $data['alias'];
        $result->isHidden = $data['isHidden'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
