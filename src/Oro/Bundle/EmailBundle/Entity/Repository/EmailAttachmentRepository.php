<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

class EmailAttachmentRepository extends EntityRepository
{
    /**
     * Get attachments in thread of current one
     *
     * @param EmailThread $thread
     *
     * @return EmailAttachment[]
     */
    public function getThreadAttachments(EmailThread $thread)
    {
        $queryBuilder = $this->createQueryBuilder('ea')
            ->leftJoin('ea.emailBody', 'eb')
            ->leftJoin('eb.email', 'e')
            ->andWhere('e.thread = :thread')
            ->setParameter('thread', $thread);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }
}
