<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;

/**
 * Model that contains security metadata for class by security type
 */
class EntitySecurityMetadata implements AclClassInfo, \Serializable
{
    /** @var string */
    protected $securityType;

    /** @var string */
    protected $className;

    /** @var string */
    protected $group;

    /** @var string */
    protected $label;

    /** @var string[] */
    protected $permissions;

    /** @var string */
    protected $description;

    /** @var string */
    protected $category;

    /** @var array|FieldSecurityMetadata[] */
    protected $fields;

    /** @var bool */
    protected $translated = false;

    /**
     * Constructor
     *
     * @param string $securityType
     * @param string $className
     * @param string $group
     * @param string $label
     * @param string[] $permissions
     * @param string $description
     * @param string $category
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
        $this->className    = $className;
        $this->group        = $group;
        $this->label        = $label;
        $this->permissions  = $permissions;
        $this->description  = $description;
        $this->category     = $category;
        $this->fields       = $fields;
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

    /**
     * Gets an entity class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Gets a security group name
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Gets an entity label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return EntitySecurityMetadata
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
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

    /**
     * Gets an action description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return EntitySecurityMetadata
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return array|FieldSecurityMetadata[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $fields
     * @return EntitySecurityMetadata
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTranslated(): bool
    {
        return $this->translated;
    }

    /**
     * @param bool $translated
     * @return EntitySecurityMetadata
     */
    public function setTranslated(bool $translated): self
    {
        $this->translated = $translated;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->securityType,
                $this->className,
                $this->group,
                $this->label,
                $this->permissions,
                $this->description,
                $this->category,
                $this->fields
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->securityType,
            $this->className,
            $this->group,
            $this->label,
            $this->permissions,
            $this->description,
            $this->category,
            $this->fields
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return EntitySecurityMetadata A new instance of a EntitySecurityMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result               = new EntitySecurityMetadata();
        $result->securityType = $data['securityType'];
        $result->className    = $data['className'];
        $result->group        = $data['group'];
        $result->label        = $data['label'];
        $result->permissions  = $data['permissions'];
        $result->description  = $data['description'];
        $result->category     = $data['category'];
        $result->fields       = $data['fields'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
