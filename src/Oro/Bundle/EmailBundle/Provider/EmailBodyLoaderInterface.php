<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

/**
 * Represents a service that provides ability to load email body.
 */
interface EmailBodyLoaderInterface
{
    /**
     * Checks if this loader can be used to load an email body from the given origin.
     */
    public function supports(EmailOrigin $origin): bool;

    /**
     * Loads email body for the given email.
     */
    public function loadEmailBody(EmailFolder $folder, Email $email, EntityManagerInterface $em): EmailBody;
}
