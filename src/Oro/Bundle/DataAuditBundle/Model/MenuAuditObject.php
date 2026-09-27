<?php

namespace Oro\Bundle\DataAuditBundle\Model;

/**
 * Menu item is recorded under in the Data Audit
 */
final class MenuAuditObject
{
    private const string SEPARATOR = '_';

    public function __construct(
        private readonly string $objectClass,
        private readonly string $menu,
        private readonly ?int $scopeId,
        private readonly string $key
    ) {
    }

    public function getObjectClass(): string
    {
        return $this->objectClass;
    }

    public function getObjectId(): string
    {
        return implode(self::SEPARATOR, [(int)$this->scopeId, $this->menu, $this->key]);
    }
}
