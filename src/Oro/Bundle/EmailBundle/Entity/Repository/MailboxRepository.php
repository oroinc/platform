<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
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
     * @param Organization $organization
     *
     * @return Collection|Mailbox[] Array or collection of Mailboxes
     */
    public function findAvailableMailboxes($user, $organization)
    {
        $qb = $this->createAvailableMailboxesQuery($user, $organization);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a list of ids of mailboxes available to user.
     *
     * @param User|integer $user User or user id
     * @param Organization $organization
     *
     * @return array Array of ids
     */
    public function findAvailableMailboxIds($user, $organization)
    {
        $mailboxes = $this->findAvailableMailboxes($user, $organization);

        $ids = [];
        foreach ($mailboxes as $mailbox) {
            $ids[] = $mailbox->getId();
        }

        return $ids;
    }

    /**
     * @param User|integer $user User or user id
     * @param Organization $organization
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function createAvailableMailboxesQuery($user, $organization)
    {
        if (!$user instanceof User) {
            $user = $this->getEntityManager()->getRepository('OroUserBundle:User')->find($user);
        }
        if (!$organization instanceof Organization) {
            $organization = $this->getEntityManager()
                ->getRepository('OroOrganizationBundle:Organization')
                ->find($organization);
        }

        $roles = $this->getUserRoleIds($user);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb')
            ->from('OroEmailBundle:Mailbox', 'mb')
            ->leftJoin('mb.authorizedUsers', 'au')
            ->leftJoin('mb.authorizedRoles', 'ar')
            ->andWhere('mb.organization = :organization')
            ->andWhere(
                $qb->expr()->orX(
                    'au = :user',
                    $qb->expr()->in('ar', ':roles')
                )
            );
        $qb->setParameter('user', $user);
        $qb->setParameter('roles', $roles);
        $qb->setParameter('organization', $organization);

        return $qb;
    }

    /**
     * Finds all mailboxes containing provided email and with settings of provided class.
     *
     * @param string $settingsClass Fully qualified class name of settings entity of mailbox.
     * @param Email  $email         Email which should be in mailbox.
     *
     * @return Collection|Mailbox[]
     */
    public function findBySettingsClassAndEmail($settingsClass, Email $email)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('mb')
           ->from('OroEmailBundle:Mailbox', 'mb')
           ->leftJoin('mb.emailUsers', 'eu')
           ->leftJoin('eu.folders', 'f')
           ->leftJoin('mb.processSettings', 'ps')
           ->where($qb->expr()->isInstanceOf('ps', $settingsClass))
           ->andWhere('eu.email = :email')
           ->andWhere(
               $qb->expr()->orX(
                   'f.type = \'inbox\'',
                   'f.type = \'other\''
               )
           );

        $qb->setParameter('email', $email);

        return $qb->getQuery()->getResult();
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
            ->andWhere('m.email != :email_address')
            ->setParameter('email', $email)
            ->setParameter('email_address', $email->getFromEmailAddress()->getEmail())
            ->getQuery()->getResult();
    }
}
