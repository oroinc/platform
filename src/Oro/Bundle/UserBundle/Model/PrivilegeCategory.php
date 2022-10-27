<?php

namespace Oro\Bundle\UserBundle\Model;

/**
 * Represents an ACL category.
 */
class PrivilegeCategory
{
    /** @var string */
    private $id;

    /** @var string */
    private $label;

    /** @var bool */
    private $tab;

    /** @var int */
    private $priority;

    public function __construct(string $id, string $label, bool $tab, int $priority)
    {
        $this->id = $id;
        $this->label = $label;
        $this->tab = $tab;
        $this->priority = $priority;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isTab(): bool
    {
        return $this->tab;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
