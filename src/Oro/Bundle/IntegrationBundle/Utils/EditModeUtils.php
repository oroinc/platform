<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

class EditModeUtils
{
    /**
     * @param int $editMode
     *
     * @return bool
     */
    public static function isEditAllowed($editMode)
    {
        return in_array($editMode, array(
            Channel::EDIT_MODE_ALLOW,
            Channel::EDIT_MODE_FORCED_ALLOW,
            Channel::EDIT_MODE_RESTRICTED
        ), true);
    }

    /**
     * @param Channel $channel
     * @param int $newEditMode
     */
    public static function attemptChangeEditMode(Channel $channel, $newEditMode)
    {
        if (false == in_array($channel->getEditMode(), array(
            Channel::EDIT_MODE_FORCED_ALLOW,
            Channel::EDIT_MODE_FORCED_DISALLOW,
        ), true)) {
            $channel->setEditMode($newEditMode);
        }
    }
}
