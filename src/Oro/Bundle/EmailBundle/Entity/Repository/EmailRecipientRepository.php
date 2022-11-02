<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for EmailRecipient entity.
 */
class EmailRecipientRepository extends EntityRepository
{
    public function getEmailsUsedInLast30DaysQb(
        array $senderEmails = [],
        array $excludedEmails = [],
        ?string $query = null
    ): QueryBuilder {
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
            ->setParameter('from', new \DateTime('-30 days'), Types::DATETIME_MUTABLE);

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
