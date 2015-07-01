<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

interface EmailFolderLoaderInterface
{
    /**
     * Checks if this loader can be used to load an email body from the given origin.
     *
     * @param EmailOrigin $origin
     * @return bool
     */
    public function supports(EmailOrigin $origin);

    /**
     * Loads email body for the given email
     *
     * @param EmailOrigin $email
     *
     * @return EmailFolder
     */
    public function loadEmailFolders(EmailOrigin $email);
}
