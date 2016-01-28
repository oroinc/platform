<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class MailboxManager
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Returns a list of ids of mailboxes available to user logged under organization.
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
     * Returns a list of mailboxes available to user logged under organization.
     *
     * @param User|integer $user User or user id
     * @param Organization|null $organization
     *
     * @return Collection|Mailbox[] Array or collection of Mailboxes
     */
    public function findAvailableMailboxes($user, Organization $organization = null)
    {
        return $this->createAvailableMailboxesQuery($user, $organization)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns a list of mailbox emails available to user logged under organization.
     *
     * @param User|integer $user User or user id
     * @param Organization|null $organization
     *
     * @return string[] Emails
     */
    public function findAvailableMailboxEmails($user, Organization $organization = null)
    {
        $result = $this->createAvailableMailboxesQuery($user, $organization)
            ->select('mb.email AS email')
            ->getQuery()
            ->getResult();

        return array_map('current', $result);
    }

    /**
     * @param User $user
     * @param Organization $organization
     *
     * @return EmailOrigin
     */
    public function findAvailableOrigins(User $user, Organization $organization)
    {
        return $this->registry->getRepository('OroEmailBundle:EmailOrigin')->findBy([
            'owner' => $user,
            'organization' => $organization,
            'isActive' => true,
        ]);
    }

    /**
     * @param User|integer $user User or user id
     * @param Organization|integer|null $organization
     *
     * @return QueryBuilder
     */
    protected function createAvailableMailboxesQuery($user, Organization $organization = null)
    {
        return $this->registry->getRepository('OroEmailBundle:Mailbox')
            ->createAvailableMailboxesQuery($user, $organization);
    }
}
