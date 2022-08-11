<?php

namespace Oro\Bundle\EmailBundle\Entity;

use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;

/**
 * Represents an email owner
 */
interface EmailOwnerInterface extends FirstNameInterface, LastNameInterface
{
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
}
