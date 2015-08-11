<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\EmailThread;

class EmailRecipientRepository extends EntityRepository
{
    /**
     * Get recipients in thread of current one
     *
     * @param EmailThread $thread
     *
     * @return EmailRecipient[]
     */
    public function getThreadUniqueRecipients(EmailThread $thread)
    {
        $filterQuery = $this->createQueryBuilder('ef')
            ->select('MIN(ef.id)')
            ->leftJoin('ef.email', 'em')
            ->andWhere('em.thread = :thread')
            ->groupBy('ef.emailAddress');
        $queryBuilder = $this->createQueryBuilder('er');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('er.id', $filterQuery->getDQL()))
            ->setParameter('thread', $thread);
        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    /**
     * @param array $senderEmails
     * @param array $excludedEmails
     * @param string|null $query
     *
     * @return QueryBuilder
     */
    public function getEmailsUsedInLast30DaysQb(
        array $senderEmails = [],
        array $excludedEmails = [],
        $query = null
    ) {
        $emailQb = $this->_em->getRepository('Oro\Bundle\EmailBundle\Entity\Email')->createQueryBuilder('e');
        $emailQb
            ->select('MAX(r.id) AS id')
            ->join('e.fromEmailAddress', 'fe')
            ->join('e.recipients', 'r')
            ->join('r.emailAddress', 'a')
            ->andWhere('e.sentAt > :from')
            ->groupBy('a.email');

        if ($senderEmails) {
            $emailQb->andWhere($emailQb->expr()->in('fe.email', ':senders'));
        } else {
            $emailQb->andWhere('1 = 0');
        }

        if ($excludedEmails) {
            $emailQb->andWhere($emailQb->expr()->notIn('a.email', ':excluded_emails'));
        }

        $recepientsQb = $this->createQueryBuilder('re');
        $recepientsQb
            ->select('re.name, ea.email')
            ->orderBy('re.name')
            ->join('re.emailAddress', 'ea')
            ->where($recepientsQb->expr()->in('re.id', $emailQb->getDQL()))
            ->setParameter('from', new \DateTime('-30 days'));

        if ($senderEmails) {
            $recepientsQb->setParameter('senders', $senderEmails);
        }

        if ($query) {
            $recepientsQb
                ->andWhere($recepientsQb->expr()->like('re.name', ':query'))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($excludedEmails) {
            $recepientsQb->setParameter('excluded_emails', $excludedEmails);
        }

        return $recepientsQb;
    }
}
