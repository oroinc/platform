<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;

class EntityFieldSecurityMetadata implements AclClassInfo, \Serializable
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
     * @var array|FieldSecurityMetadata[]
     */
    protected $fields;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $label;

    /**
     * Constructor
     *
     * @param string $securityType
     * @param string $entityClassName
     * @param string $group
     * @param string $label
     */
    public function __construct(
        $securityType = '',
        $className = '',
        $group = '',
        $label = '',
        $fields = []
    ) {
        $this->securityType = $securityType;
        $this->className = $className;
        $this->group = $group;
        $this->label = $label;
        $this->fields = $fields;
    }

    /**
     * @return array|FieldSecurityMetadata[]
     */
    public function getFields()
    {
        return $this->fields;
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
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->securityType,
                $this->className,
                $this->group,
                $this->label,
                $this->fields
            ]
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
            $this->fields
            ) = unserialize($serialized);
    }

    /**
     * The __set_state handler
     *
     * @param array $data Initialization array
     *
     * @return EntityFieldSecurityMetadata A new instance of a EntityFieldSecurityMetadata object
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        $result = new EntityFieldSecurityMetadata();
        $result->securityType = $data['securityType'];
        $result->className = $data['className'];
        $result->group = $data['group'];
        $result->label = $data['label'];
        $result->fields = $data['fields'];

        return $result;
    }
    // @codingStandardsIgnoreEnd
}
