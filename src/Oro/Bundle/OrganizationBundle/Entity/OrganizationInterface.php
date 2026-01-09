<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

/**
 * Defines the contract for organization entities.
 *
 * Organizations represent the top-level organizational structure in the Oro platform.
 * Implementing classes must provide access to the organization's unique identifier and name.
 */
interface OrganizationInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();
}
