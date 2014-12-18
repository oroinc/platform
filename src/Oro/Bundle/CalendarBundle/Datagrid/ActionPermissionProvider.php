<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class ActionPermissionProvider
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade      $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getInvitationPermissions(ResultRecordInterface $record)
    {
        /** @var User $user */
        $user = $this->securityFacade->getLoggedUser();
        $invitationStatus = $record->getValue('invitationStatus');
        $parentId = $record->getValue('parentId');
        $ownerId = $record->getValue('ownerId');
        $childrenCount = $record->getValue('childrenCount');

        $isEditable = !$invitationStatus || ($invitationStatus && !$parentId);

        return array(
            'accept'      => $this->isAvailableResponseButton(
                $user,
                $parentId,
                $ownerId,
                $childrenCount,
                $invitationStatus,
                CalendarEvent::ACCEPTED
            ),
            'decline'     => $this->isAvailableResponseButton(
                $user,
                $parentId,
                $ownerId,
                $childrenCount,
                $invitationStatus,
                CalendarEvent::DECLINED
            ),
            'tentatively' => $this->isAvailableResponseButton(
                $user,
                $parentId,
                $ownerId,
                $childrenCount,
                $invitationStatus,
                CalendarEvent::TENTATIVELY_ACCEPTED
            ),
            'view'        => true,
            'update'      => $isEditable
        );
    }

    /**
     * @param User $user
     * @param int $parentId
     * @param int $ownerId
     * @param int $childrenCount
     * @param string $invitationStatus
     * @param string $buttonStatus
     * @return bool
     */
    protected function isAvailableResponseButton(
        $user,
        $parentId,
        $ownerId,
        $childrenCount,
        $invitationStatus,
        $buttonStatus
    ) {
        return $invitationStatus
        && $invitationStatus != $buttonStatus
        && $user->getId() == $ownerId
        && ($parentId || $childrenCount);
    }
}
