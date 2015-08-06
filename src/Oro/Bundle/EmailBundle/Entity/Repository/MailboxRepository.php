<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
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
     * Returns a list of mailboxes available to user.
     *
     * @param User|integer $user User or user id
     *
     * @return Collection|Mailbox[] Array or collection of Mailboxes
     */
    public function findAvailableMailboxes($user)
    {
        if (!$user instanceof User) {
            $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($user);
        }

        $roles = $this->getUserRoleIds($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb')
           ->from('OroEmailBundle:Mailbox', 'mb')
           ->leftJoin('mb.authorizedUsers', 'au')
           ->leftJoin('mb.authorizedRoles', 'ar')
           ->where('au = :user')
           ->orWhere(
               $qb->expr()->in('ar', $roles)
           );
        $qb->setParameter('user', $user);

        return $qb->getQuery()->execute();
    }

    /**
     * Returns a list of ids of mailboxes available to user.
     *
     * @param User|integer $user User or user id
     *
     * @return array Array of ids
     */
    public function findAvailableMailboxIds($user)
    {
        if (!$user instanceof User) {
            $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($user);
        }

        $roles = $this->getUserRoleIds($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb.id')
            ->from('OroEmailBundle:Mailbox', 'mb')
            ->leftJoin('mb.authorizedUsers', 'au')
            ->leftJoin('mb.authorizedRoles', 'ar')
            ->where('au = :user')
            ->orWhere(
                $qb->expr()->in('ar', $roles)
            );
        $qb->setParameter('user', $user);

        return array_map('current', $qb->getQuery()->getScalarResult());
    }

    /**
     * Returns a list of ids of roles assigned ot user.
     *
     * @param User $user
     *
     * @return array Array of ids
     */
    protected function getUserRoleIds(User $user)
    {
        // Get ids of all user roles.
        $roles = $user->getRoles();
        $roleList = array_map(
            function ($value) {
                return $value->getId();
            },
            $roles
        );

        return $roleList;
    }
}
