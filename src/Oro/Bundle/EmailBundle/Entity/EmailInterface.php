<?php

namespace Oro\Bundle\EmailBundle\Entity;

/**
 * Defines the contract for entities that contain email address information.
 *
 * Entities implementing this interface must provide access to their email field name,
 * unique identifier, email address, and associated email owner for email-related operations.
 */
interface EmailInterface
{
    /**
     * Get name of field contains an email address
     *
     * @return string
     */
    public function getEmailField();

    /**
     * Get entity unique id
     *
     * @return integer
     */
    public function getId();

    /**
     * Get email address
     *
     * @return string
     */
    public function getEmail();

    /**
     * Get email owner entity
     *
     * @return EmailOwnerInterface
     */
    public function getEmailOwner();
}
