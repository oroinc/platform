<?php

namespace Oro\Bundle\NotificationBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

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
     * @param ArrayCollection $emails
     * @param RecipientList   $recipientList
     */
    protected function addGroupUsersEmails(ArrayCollection $emails, RecipientList $recipientList)
    {
        $groupIds = $recipientList->getGroups()->map(
            function ($group) {
                return $group->getId();
            }
        )->toArray();

        if (!$groupIds) {
            return;
        }

        $groupUsers = $this->_em->createQueryBuilder()
            ->select('u.email')
            ->from('OroUserBundle:User', 'u')
            ->leftJoin('u.groups', 'groups')
            ->where('groups.id IN (:groupIds)')
            ->setParameter('groupIds', $groupIds)
            ->getQuery()
            ->getResult();

        array_map(
            function ($groupEmail) use ($emails) {
                $emails->add($groupEmail['email']);
            },
            $groupUsers
        );
    }
}
