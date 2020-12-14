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

    /**
     * @param string $id
     * @param string $label
     * @param bool   $tab
     * @param int    $priority
     */
    public function __construct(string $id, string $label, bool $tab, int $priority)
    {
        $this->id = $id;
        $this->label = $label;
        $this->tab = $tab;
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return bool
     */
    public function isTab(): bool
    {
        return $this->tab;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
}
