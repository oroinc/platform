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
    public function findOneByOrigin(EmailOrigin $origin)
    {
        return $this->findOneBy(['origin' => $origin]);
    }

    /**
     * @param string $email
     *
     * @return null|Mailbox
     */
    public function findOneByEmail($email)
    {
        return $this->findOneBy(['email' => $email]);
    }
}
