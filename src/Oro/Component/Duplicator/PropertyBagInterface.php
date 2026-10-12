<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator;

/**
 * A set of named values detached from the object that owns them, copied as if they were its properties.
 */
interface PropertyBagInterface
{
    public function getOwnerClass(): string;

    public function toArray(): array;
}
