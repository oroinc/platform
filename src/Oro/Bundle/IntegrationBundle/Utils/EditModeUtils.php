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
        $allowedModes = [Channel::EDIT_MODE_ALLOW, Channel::EDIT_MODE_FORCED_ALLOW, Channel::EDIT_MODE_RESTRICTED];

        return in_array($editMode, $allowedModes, true);
    }

    /**
     * @param Channel $channel
     * @param int $newEditMode
     */
    public static function attemptChangeEditMode(Channel $channel, $newEditMode)
    {
        $forcedModes = [Channel::EDIT_MODE_FORCED_ALLOW, Channel::EDIT_MODE_FORCED_DISALLOW];

        if (false == in_array($channel->getEditMode(), $forcedModes, true)) {
            $channel->setEditMode($newEditMode);
        }
    }
}
