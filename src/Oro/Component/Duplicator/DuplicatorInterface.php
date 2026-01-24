<?php

namespace Oro\Component\Duplicator;

/**
 * Defines the contract for object duplication with customizable settings.
 *
 * Implementations provide the ability to create deep copies of objects with
 * fine-grained control over which properties are copied and how they are handled.
 */
interface DuplicatorInterface
{
    /**
     * @param object $object
     * @param array $settings
     * @return mixed
     */
    public function duplicate($object, array $settings = []);
}
