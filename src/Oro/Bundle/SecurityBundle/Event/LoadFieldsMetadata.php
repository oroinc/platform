<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched when field security metadata is being loaded for an entity.
 *
 * This event is fired by the {@see \Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider}
 * when loading field ACL metadata. Event listeners can modify the fields list to apply additional conditions
 * or customize field security metadata before it is cached and used.
 */
class LoadFieldsMetadata extends Event
{
    public const NAME = 'oro_security.event.load_fields_metadata.after';

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
