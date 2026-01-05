<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * Represents security metadata for an entity.
 */
class EntitySecurityMetadata implements ClassSecurityMetadata
{
    /** @var string */
    private $securityType;

    /** @var string */
    private $className;

    /** @var string */
    private $group;

    /** @var string */
    private $label;

    /** @var string[] */
    private $permissions;

    /** @var string */
    private $description;

    /** @var string */
    private $category;

    /** @var FieldSecurityMetadata[] */
    private $fields;

    /**
     * @param string                  $securityType
     * @param string                  $className
     * @param string                  $group
     * @param string                  $label
     * @param string[]                $permissions
     * @param string                  $description
     * @param string                  $category
     * @param FieldSecurityMetadata[] $fields
     */
    public function __construct(
        $securityType = '',
        $className = '',
        $group = '',
        $label = '',
        $permissions = [],
        $description = '',
        $category = '',
        $fields = []
    ) {
        $this->securityType = $securityType;
        $this->className = $className;
        $this->group = $group;
        $this->label = $label;
        $this->permissions = $permissions;
        $this->description = $description;
        $this->category = $category;
        $this->fields = $fields;
    }

    /**
     * Gets the security type
     *
     * @return string
     */
    public function getSecurityType()
    {
        return $this->securityType;
    }

    #[\Override]
    public function getClassName()
    {
        return $this->className;
    }

    #[\Override]
    public function getGroup()
    {
        return $this->group;
    }

    #[\Override]
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Gets permissions
     *
     * @return string[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    #[\Override]
    public function getDescription()
    {
        return $this->description;
    }

    #[\Override]
    public function getCategory()
    {
        return $this->category;
    }

    #[\Override]
    public function getFields()
    {
        return $this->fields;
    }

    public function __serialize(): array
    {
        return [
            $this->securityType,
            $this->className,
            $this->group,
            $this->label,
            $this->permissions,
            $this->description,
            $this->category,
            $this->fields
        ];
    }

    public function __unserialize(array $serialized): void
    {
        [
            $this->securityType,
            $this->className,
            $this->group,
            $this->label,
            $this->permissions,
            $this->description,
            $this->category,
            $this->fields
        ] = $serialized;
    }

    /**
     * @param array $data
     *
     * @return EntitySecurityMetadata
     */
    // phpcs:disable
    public static function __set_state($data)
    {
        return new EntitySecurityMetadata(
            $data['securityType'],
            $data['className'],
            $data['group'],
            $data['label'],
            $data['permissions'],
            $data['description'],
            $data['category'],
            $data['fields']
        );
    }
    // phpcs:enable
}
