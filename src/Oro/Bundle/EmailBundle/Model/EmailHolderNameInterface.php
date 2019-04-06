<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Represents full name associated with email address holder
 */
interface EmailHolderNameInterface
{
    /**
     * Gets full name associated with email address holder
     *
     * @return string
     */
    public function getEmailHolderName();
}
