<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;

class ActionMetadata implements AclClassInfo, \Serializable
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $label;

    /**
     * Gets an action name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->name;
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
     * Gets an action label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Constructor
     *
     * @param string $name
     * @param string $group
     * @param string $label
     */
    public function __construct($name = '', $group = '', $label = '')
    {
        $this->name  = $name;
        $this->group = $group;
        $this->label = $label;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->name,
                $this->group,
                $this->label
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list(
            $this->name,
            $this->group,
            $this->label
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     * @return ActionMetadata A new instance of a ActionMetadata object
     */
    public static function __set_state($data)
    {
        $result        = new ActionMetadata();
        $result->name  = $data['name'];
        $result->group = $data['group'];
        $result->label = $data['label'];

        return $result;
    }
}
