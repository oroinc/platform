<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;

/**
 * Allows to get an email flag manager for internal emails.
 */
class InternalEmailFlagManagerLoader implements EmailFlagManagerLoaderInterface
{
    #[\Override]
    public function supports(EmailOrigin $origin): bool
    {
        return $origin instanceof InternalEmailOrigin;
    }

    #[\Override]
    public function select(EmailFolder $folder, EntityManagerInterface $em): EmailFlagManagerInterface
    {
        return new InternalEmailFlagManager();
    }
}
