<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Symfony\Component\EventDispatcher\Event;

/**
 * @see \Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider
 * This event is fired by the EntitySecurityMetadataProvider when fields ACL metadata being loaded.
 * The listeners of this event may modify the inner fields list to apply additional conditions.
 */
class LoadFieldsMetadata extends Event
{
    const NAME = 'oro_security.event.load_fields_metadata.after';

    /** @var array */
    protected $fields;

    /** @var string */
    protected $className;

    /**
     * @param string                  $className
     * @param FieldSecurityMetadata[] $fields
     */
    public function __construct($className, $fields)
    {
        $this->className = $className;
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return FieldSecurityMetadata[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param FieldSecurityMetadata[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}
