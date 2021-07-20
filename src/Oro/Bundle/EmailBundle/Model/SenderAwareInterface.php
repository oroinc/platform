<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Is suitable for classes which are aware of an email sender.
 */
interface SenderAwareInterface
{
    public function getSender(): ?From;
}
