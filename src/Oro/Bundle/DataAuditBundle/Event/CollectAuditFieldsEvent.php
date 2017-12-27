<?php

namespace Oro\Bundle\DataAuditBundle\Event;

use Oro\Bundle\DataAuditBundle\Entity\AbstractAuditField;
use Symfony\Component\EventDispatcher\Event;

class CollectAuditFieldsEvent extends Event
{
    const NAME = 'oro_audit.collect_audit_fields';

    /**
     * @var string
     */
    private $auditFieldClass;

    /**
     * @var array
     */
    private $changeSet;

    /**
     * @var array
     */
    private $fields;

    /**
     * @param string $auditFieldClass
     * @param array $changeSet
     * @param array $fields
     */
    public function __construct(string $auditFieldClass, array $changeSet, array $fields)
    {
        $this->auditFieldClass = $auditFieldClass;
        $this->changeSet = $changeSet;
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getAuditFieldClass(): string
    {
        return $this->auditFieldClass;
    }

    /**
     * @return array
     */
    public function getChangeSet(): array
    {
        return $this->changeSet;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $name
     * @param AbstractAuditField $field
     * @return $this
     */
    public function addField(string $name, AbstractAuditField $field)
    {
        $this->fields[$name] = $field;

        return $this;
    }
}
