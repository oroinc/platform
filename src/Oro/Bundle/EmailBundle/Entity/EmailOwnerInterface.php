<?php

namespace Oro\Bundle\EmailBundle\Entity;

/**
 * Represents an email owner
 */
interface EmailOwnerInterface
{
    /**
     * Get entity class name.
     * TODO: This is a temporary solution for get 'view' route in twig.
     *       Will be removed after EntityConfigBundle is finished
     *
     * @return string
     */
    public function getClass();

    /**
     * Get names of fields contain email addresses
     *
     * @return string[]|null
     */
    public function getEmailFields();

    /**
     * Get entity unique id
     *
     * @return integer
     */
    public function getId();

    /**
     * Get name of email owner.
     *
     * @return string
     */
    public function getEmailOwnerName();
}
