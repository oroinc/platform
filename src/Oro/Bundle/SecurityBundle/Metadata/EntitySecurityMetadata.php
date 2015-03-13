<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;

class EntitySecurityMetadata implements AclClassInfo, \Serializable
{
    /**
     * @var string
     */
    protected $securityType;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string[]
     */
    protected $permissions;

    /**
     * @var array
     */
    protected $additionalParams;

    /**
     * Constructor
     *
     * @param string   $securityType
     * @param string   $className
     * @param string   $group
     * @param string   $label
     * @param array    $permissions
     * @param array    $additionalParams
     */
    public function __construct(
        $securityType = '',
        $className = '',
        $group = '',
        $label = '',
        $permissions = [],
        $additionalParams = []
    ) {
        $this->securityType     = $securityType;
        $this->className        = $className;
        $this->group            = $group;
        $this->label            = $label;
        $this->permissions      = $permissions;
        $this->additionalParams = $additionalParams;
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
     * Gets permissions
     *
     * @return string[]
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * @return array
     */
    public function getAdditionalParams()
    {
        return $this->additionalParams;
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
                $this->additionalParams
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
            $this->additionalParams
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
     * @return EntitySecurityMetadata A new instance of a EntitySecurityMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result                   = new EntitySecurityMetadata();
        $result->securityType     = $data['securityType'];
        $result->className        = $data['className'];
        $result->group            = $data['group'];
        $result->label            = $data['label'];
        $result->permissions      = $data['permissions'];
        $result->additionalParams = $data['additionalParams'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
