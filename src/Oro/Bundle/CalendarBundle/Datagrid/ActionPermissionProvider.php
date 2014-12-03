<?php

namespace Oro\Bundle\CalendarBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class ActionPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getInvitationPermissions(ResultRecordInterface $record)
    {
        $invitationStatus = $record->getValue('invitationStatus');
        $parentId = $record->getValue('parentId');

        $isEnableAccepted = $invitationStatus && $invitationStatus != CalendarEvent::ACCEPTED;
        $isEnableTentatively = $invitationStatus && $invitationStatus != CalendarEvent::TENTATIVELY_ACCEPTED;
        $isEnableDecline = $invitationStatus && $invitationStatus != CalendarEvent::DECLINED;

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
