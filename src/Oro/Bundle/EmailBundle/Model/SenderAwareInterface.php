<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Is suitable for classes which are aware of an email sender.
 */
interface SenderAwareInterface
{
    /**
     * @return From|null
     */
    public function getSender(): ?From;
}
