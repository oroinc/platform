<?php

namespace Oro\Bundle\EntityExtendBundle\Entity;

/**
 * Base enum option interface.
 */
interface EnumOptionInterface
{
    public function getId(): string;

    public function setPriority($priority): static;

    public function getPriority(): int;

    public function setDefault(bool $default): static;

    public function isDefault(): bool;

    public function getInternalId(): string;

    public function getEnumCode(): string;
}
