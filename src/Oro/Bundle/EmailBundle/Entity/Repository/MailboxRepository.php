<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;


use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;

class MailboxRepository extends EntityRepository
{
    /**
     * @param EmailOrigin $origin
     *
     * @return null|Mailbox
     */
    public function findByOrigin(EmailOrigin $origin)
    {
        return $this->findOneBy(['origin' => $origin]);
    }
}
