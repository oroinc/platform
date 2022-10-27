<?php

namespace Oro\Bundle\EmailBundle\Entity;

/**
 * Intended for entities which hold email owner.
 */
interface EmailOwnerAwareInterface
{
    public function getEmailOwner(): EmailOwnerInterface;
}
