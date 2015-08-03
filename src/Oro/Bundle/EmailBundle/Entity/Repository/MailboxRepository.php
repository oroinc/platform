<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\UserBundle\Entity\User;

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
     * @param User|integer $user    User entity or user id.
     * @param bool         $idsOnly Whether to return ids only or not.
     *
     * @return \integer[]|mixed
     */
    public function findAvailableMailboxes($user, $idsOnly = true)
    {
        if (!$user instanceof User) {
            $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($user);
        }

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
        if ($idsOnly) {
            $qb->select('mb.id');
        } else {
            $qb->select('mb');
        }
        $qb->from('OroEmailBundle:Mailbox', 'mb')
            ->leftJoin('mb.authorizedUsers', 'au')
            ->leftJoin('mb.authorizedRoles', 'ar')
            ->where('au = :user')
            ->orWhere(
                $qb->expr()->in('ar', $roleList)
            );
        $qb->setParameter('user', $user);

        if ($idsOnly) {
            return array_map('current', $qb->getQuery()->getScalarResult());
        }

        return $qb->getQuery()->execute();
    }
}
