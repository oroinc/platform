<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Symfony\Component\Security\Core\Role\RoleInterface;

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
     * @param integer $userId
     *
     * @return integer[]
     */
    public function findIdsOfAllAuthorized($userId)
    {
        $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($userId);

        $roles = $user->getRoles();

        $roleList = array_map(
            function ($value) {
                return $value->getId();
            },
            $roles
        );

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb.id')
            ->from('OroEmailBundle:Mailbox', 'mb')
            ->leftJoin('mb.authorizedUsers', 'au')
            ->leftJoin('mb.authorizedRoles', 'ar')
            ->where('au = :userId')
            ->orWhere(
                $qb->expr()->in('ar', $roleList)
            );

        $qb->setParameters(
            [
                'userId' => $userId
            ]
        );

        return array_map('current', $qb->getQuery()->getScalarResult());
    }
}
