<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Entity;

/**
 * Marker for extend entities
 */
interface ExtendEntityInterface
{
    /**
     * Gets extended property value
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name): mixed;

    /**
     * Sets extended property as property to entity
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set(string $name, mixed $value): static;
}
