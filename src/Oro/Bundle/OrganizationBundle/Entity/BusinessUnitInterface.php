<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

/**
 * Defines the contract for business unit entities.
 *
 * Business units represent organizational divisions or departments within an organization.
 * Implementing classes must provide access to the business unit's unique identifier and name.
 */
interface BusinessUnitInterface
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
