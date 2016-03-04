<?php

namespace Oro\Bundle\EntityBundle\ORM;

class ShortClassMetadata implements \Serializable
{
    /**
     * READ-ONLY: The name of the entity class.
     *
     * @var string
     */
    public $name;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public $isMappedSuperclass;

    /**
     * @param string $name
     * @param bool   $isMappedSuperclass
     */
    public function __construct($name, $isMappedSuperclass = false)
    {
        $this->name = $name;
        $this->isMappedSuperclass = $isMappedSuperclass;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([$this->name, $this->isMappedSuperclass]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->name, $this->isMappedSuperclass) = unserialize($serialized);
    }

    /**
     * @param array $data
     *
     * @return ShortClassMetadata
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new ShortClassMetadata($data['name'], $data['isMappedSuperclass']);
    }
    // @codingStandardsIgnoreEnd
}
