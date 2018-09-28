<?php

namespace Oro\Bundle\NotificationBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Responsible for fetching recipients based on recipient list entity which includes direct users and users in groups.
 */
class RecipientListRepository extends EntityRepository
{
    /**
     * @param RecipientList $recipientList
     *
     * @return array
     */
    public function getRecipientEmails(RecipientList $recipientList)
    {
        // get user emails
        $emails = $recipientList->getUsers()->map(
            function (EmailHolderInterface $user) {
                return $user->getEmail();
            }
        );

        $this->addGroupUsersEmails($emails, $recipientList);

        // add custom email
        if ($recipientList->getEmail()) {
            $emails[] = $recipientList->getEmail();
        }

        return array_unique($emails->toArray());
    }

    /**
     * @param RecipientList $recipientList
     *
     * @return array
     */
    public function getRecipients(RecipientList $recipientList): array
    {
        $users = $recipientList->getUsers();
        $groupUsers = $this->getGroupRecipients($recipientList);

        $recipients = array_merge($users->toArray(), $groupUsers);

        // add custom email
        if ($recipientList->getEmail()) {
            $found = false;
            /** @var EmailHolderInterface $recipient */
            foreach ($recipients as $recipient) {
                if ($recipient->getEmail() === $recipientList->getEmail()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $recipients[] = new EmailAddressWithContext($recipientList->getEmail());
            }
        }

        return $recipients;
    }

    /**
     * @param ArrayCollection $emails
     * @param RecipientList   $recipientList
     */
    protected function addGroupUsersEmails(ArrayCollection $emails, RecipientList $recipientList)
    {
        if ($recipientList->getGroups()->isEmpty()) {
            return;
        }

        $groupUsers = $this->getUserEmailsInGroupsQueryBuilder($recipientList->getGroups())
            ->getQuery()
            ->getResult();

        array_map(
            function ($groupEmail) use ($emails) {
                $emails->add($groupEmail['email']);
            },
            $groupUsers
        );
    }

    /**
     * @param Collection $groups
     * @return QueryBuilder
     */
    private function getUserEmailsInGroupsQueryBuilder(Collection $groups): QueryBuilder
    {
        return $this->_em->createQueryBuilder()
            ->select('u.email')
            ->from('OroUserBundle:User', 'u')
            ->leftJoin('u.groups', 'groups')
            ->where('groups.id IN (:groupIds)')
            ->setParameter('groupIds', $groups);
    }

    /**
     * @param RecipientList $recipientList
     * @return array|EmailHolderInterface[]
     */
    private function getGroupRecipients(RecipientList $recipientList): array
    {
        if ($recipientList->getGroups()->isEmpty()) {
            return [];
        }

        $queryBuilder = $this->getUserEmailsInGroupsQueryBuilder($recipientList->getGroups())->addSelect('u.id');

        if (!$recipientList->getUsers()->isEmpty()) {
            // Filter out user recipients which were selected directly
            $queryBuilder
                ->andWhere('u.id not IN (:users)')
                ->setParameter('users', $recipientList->getUsers());
        }

        $groupUsers = $queryBuilder->getQuery()->getArrayResult();

        $recipients = [];
        foreach ($groupUsers as $groupUser) {
            $recipients[] = new EmailAddressWithContext(
                $groupUser['email'],
                $this->_em->getReference(User::class, $groupUser['id'])
            );
        }

        return $recipients;
    }
}
