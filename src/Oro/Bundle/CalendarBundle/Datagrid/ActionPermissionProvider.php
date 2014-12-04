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

        $isEnableAccepted = $invitationStatus
            && $invitationStatus != CalendarEvent::ACCEPTED
            && $user->getId() == $ownerId;
        $isEnableTentatively = $invitationStatus
            && $invitationStatus != CalendarEvent::TENTATIVELY_ACCEPTED
            && $user->getId() == $ownerId;
        $isEnableDecline = $invitationStatus
            && $invitationStatus != CalendarEvent::DECLINED
            && $user->getId() == $ownerId;

        $isEditable = !$invitationStatus || ($invitationStatus && !$parentId);

        return array(
            'accept'      => $isEnableAccepted,
            'decline'     => $isEnableDecline,
            'tentatively' => $isEnableTentatively,
            'view'        => true,
            'update'      => $isEditable
        );
    }
}
