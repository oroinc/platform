<?php

namespace Oro\Bundle\NotificationBundle\Entity\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\NotificationBundle\Entity\NotificationEmailInterface;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class RecipientListRepository extends EntityRepository
{
    /**
     * @param RecipientList $recipientList
     * @param mixed         $entity
     * @param string        $ownerPropertyName
     *
     * @return array
     */
    public function getRecipientEmails(RecipientList $recipientList, $entity, $ownerPropertyName = null)
    {
        // get user emails
        $emails = $recipientList->getUsers()->map(
            function (EmailHolderInterface $user) {
                return $user->getEmail();
            }
        );

        $this->addGroupUsersEmails($emails, $recipientList);
        $this->addOwnerEmails($emails, $recipientList, $entity, $ownerPropertyName);

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

    /**
     * @param ArrayCollection $emails
     * @param RecipientList   $recipientList
     * @param object          $entity
     * @param string          $ownerPropertyName
     */
    protected function addOwnerEmails(
        ArrayCollection $emails,
        RecipientList $recipientList,
        $entity,
        $ownerPropertyName
    ) {
        // check if owner exists
        if ($recipientList->getOwner() && $ownerPropertyName) {
            $method = 'get'.ucfirst($ownerPropertyName);
            $owner = method_exists($entity, $method) ? $entity->$method() : null;
        } else {
            $owner = null;
        }

        if (!is_object($owner) || !$owner instanceof NotificationEmailInterface) {
            return;
        }

        $owner->getNotificationEmails()->map(
            function ($email) use ($emails) {
                $emails->add($email);
            }
        );
    }
}
