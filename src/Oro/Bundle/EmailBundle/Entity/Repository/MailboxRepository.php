<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;
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

    /**
     * @param Email $email
     *
     * @return Mailbox[]
     */
    public function findForEmail(Email $email)
    {
        $emailUsersDql = $this->_em->getRepository('OroEmailBundle:EmailUser')->createQueryBuilder('ue')
            ->select('ue.id')
            ->where('ue.email = :email')
            ->andWhere('ue.mailboxOwner = m.id')
            ->setMaxResults(1)
            ->getDQL();

        $qb = $this->createQueryBuilder('m');

        return $qb
            ->select('m')
            ->andWhere($qb->expr()->exists($emailUsersDql))
            ->setParameter('email', $email)
            ->getQuery()->getResult();
    }
}
