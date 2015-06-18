<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserRepository extends EntityRepository
{
    /**
     * @param Email $email
     * @param User  $user
     *
     * @return null|EmailUser
     */
    public function findByEmailAndOwner(Email $email, User $user)
    {
        return $this->findOneBy([
            'email' => $email,
            'owner' => $user
        ]);
    }

    /**
     * @param User $user
     * @param Organization $organization
     * @param array $folderTypes
     * @param bool $isSeen
     * @return array
     */
    public function getEmailUserList(User $user, Organization $organization, array $folderTypes = [], $isSeen = null)
    {
        $qb = $this->createQueryBuilder('eu');
        $qb
            ->join('eu.folder', 'f')
            ->andWhere($qb->expr()->eq('eu.owner', $user->getId()))
            ->andWhere($qb->expr()->eq('eu.organization', $organization->getId()));

        if ($folderTypes) {
            $qb->andWhere($qb->expr()->in('f.type', $folderTypes));
        }

        if ($isSeen !== null) {
            $qb->andWhere($qb->expr()->eq('eu.seen', $isSeen));
        }

        return $qb->getQuery()->getResult();
    }
}
