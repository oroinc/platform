<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Represents a service that allows to get an email flag manager.
 */
interface EmailFlagManagerLoaderInterface
{
    /**
     * Checks if this loader can be used to load an email body from the given origin.
     */
    public function supports(EmailOrigin $origin): bool;

    /**
     * Loads an email flag manager.
     */
    public function select(EmailFolder $folder, EntityManagerInterface $em): EmailFlagManagerInterface;
}
