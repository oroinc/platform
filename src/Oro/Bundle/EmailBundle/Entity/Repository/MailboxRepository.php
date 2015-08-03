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
     * Returns all mailbox ids accessible by user.
     *
     * @param integer $userId
     *
     * @return integer[]
     */
    public function findAvailableMailboxIds($userId)
    {
        $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($userId);

        // Get ids of all user roles.
        $roles = $user->getRoles();
        $roleList = array_map(
            function ($value) {
                return $value->getId();
            },
            $roles
        );

        // Find mailboxes which have user in list of authorized users or any of his roles in list of authorized roles.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb.id')
            ->from('OroEmailBundle:Mailbox', 'mb')
            ->leftJoin('mb.authorizedUsers', 'au')
            ->leftJoin('mb.authorizedRoles', 'ar')
            ->where('au = :user')
            ->orWhere(
                $qb->expr()->in('ar', $roleList)
            );
        $qb->setParameter('user', $userId);

        return array_map('current', $qb->getQuery()->getScalarResult());
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
