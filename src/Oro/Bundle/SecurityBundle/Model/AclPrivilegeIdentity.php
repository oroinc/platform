<?php

namespace Oro\Bundle\SecurityBundle\Model;

/**
 * Represents the identity of an ACL privilege target.
 *
 * This model class identifies what an ACL privilege applies to, storing both an
 * identifier (typically a class name or capability identifier) and a display name.
 * It is used to uniquely identify entities or capabilities within ACL privilege configurations.
 */
class AclPrivilegeIdentity
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * Constructor
     *
     * @param string|null $id
     * @param string|null $name
     */
    public function __construct($id = null, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param  string               $id
     * @return AclPrivilegeIdentity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string               $name
     * @return AclPrivilegeIdentity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
