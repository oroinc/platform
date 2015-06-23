<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

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
     * @param string|null $query
     * @param int $limit
     *
     * @return array
     */
    public function getEmailsUsedInLast30Days(array $senderEmails = [], $query = null, $limit = 100)
    {
        if (!$senderEmails) {
            return [];
        }

        $emailQb = $this->_em->getRepository('Oro\Bundle\EmailBundle\Entity\Email')->createQueryBuilder('e');
        $emailQb
            ->select('MAX(r.id) AS id')
            ->join('e.fromEmailAddress', 'fe')
            ->join('e.recipients', 'r')
            ->join('r.emailAddress', 'a')
            ->andWhere('e.sentAt > :from')
            ->andWhere($emailQb->expr()->in('fe.email', ':senders'))
            ->groupBy('a.email');

        $recepientsQb = $this->createQueryBuilder('re');
        $recepientsQb
            ->select('re.name, ea.email')
            ->join('re.emailAddress', 'ea')
            ->where($recepientsQb->expr()->in('re.id', $emailQb->getDQL()))
            ->setMaxResults($limit)
            ->setParameter('from', new \DateTime('-30 days'))
            ->setParameter('senders', $senderEmails);

        if ($query) {
            $recepientsQb
                ->andWhere($recepientsQb->expr()->like('re.name', ':query'))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        $emails = $recepientsQb->getQuery()->getResult();

        $result = [];
        foreach ($emails as $email) {
            $result[$email['email']] = $email['name'];
        }

        return $result;
    }
}
