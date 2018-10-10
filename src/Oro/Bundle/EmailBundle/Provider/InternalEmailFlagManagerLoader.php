<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Manager\InternalEmailFlagManager;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

/**
 * Loads internal email flag manager to load an email body from the internal email origin
 */
class InternalEmailFlagManagerLoader implements EmailFlagManagerLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof InternalEmailOrigin;
    }

    /**
     * {@inheritdoc}
     */
    public function select(EmailFolder $folder, OroEntityManager $em)
    {
        return new InternalEmailFlagManager();
    }
}
