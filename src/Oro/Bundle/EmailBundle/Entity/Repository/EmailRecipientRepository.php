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
     * @deprecated since 2.3. Use EmailGridResultHelper::addEmailRecipients instead
     */
    public function getThreadUniqueRecipients(EmailThread $thread)
    {
        $queryBuilder = $this->createQueryBuilder('ef')
            ->leftJoin('ef.email', 'em')
            ->andWhere('em.thread = :thread')
            ->setParameter('thread', $thread);
        $result = $queryBuilder->getQuery()->getResult();
        $recipients = array_filter(
            $result,
            function ($item) {
                /** @var EmailRecipient $item */
                static $addresses = [];
                if (in_array($item->getEmailAddress()->getEmail(), $addresses)) {
                    return false;
                }
                $addresses[] = $item->getEmailAddress()->getEmail();

                return true;
            }
        );

        return $recipients;
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
            ->select('r.id')
            ->join('e.fromEmailAddress', 'fe')
            ->join('e.recipients', 'r')
            ->join('r.emailAddress', 'a')
            ->andWhere('e.sentAt > :from');

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
            ->distinct()
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
