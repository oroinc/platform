<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
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
     * @param Organization $organization|null
     *
     * @return Collection|Mailbox[] Array or collection of Mailboxes
     */
    public function findAvailableMailboxes($user, Organization $organization = null)
    {
        $qb = $this->registry->getRepository('OroEmailBundle:Mailbox')
            ->createAvailableMailboxesQuery($user, $organization);

        return $qb->getQuery()->getResult();
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
}
