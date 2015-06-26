<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

/**
 * Interface EmailFlagManagerLoaderInterface
 * @package Oro\Bundle\EmailBundle\Provider
 */
interface EmailFlagManagerLoaderInterface
{
    /**
     * Checks if this loader can be used to load an email body from the given origin.
     *
     * @param EmailOrigin $origin
     *
     * @return bool
     */
    public function supports(EmailOrigin $origin);

    /**
     * Loads email flag manager
     *
     * @param EmailFolder      $folder
     * @param OroEntityManager $em
     *
     * @return EmailFlagManagerInterface
     */
    public function select(EmailFolder $folder, OroEntityManager $em);
}
