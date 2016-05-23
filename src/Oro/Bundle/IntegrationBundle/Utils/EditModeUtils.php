<?php

namespace Oro\Bundle\IntegrationBundle\Utils;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

class EditModeUtils
{
    /**
     * Checks if edit mode allow to edit or delete integration
     *
     * @param int $editMode
     *
     * @return bool
     */
    public static function isEditAllowed($editMode)
    {
        $notAllowed = [Integration::EDIT_MODE_DISALLOW, Integration::EDIT_MODE_RESTRICTED];

        return !in_array($editMode, $notAllowed, true);
    }

    /**
     * System use three edit mods and disallow to change edit mode
     * if edit mode set with another mode
     *
     * @param Integration $integration
     * @param int         $newEditMode
     *
     */
    public static function attemptChangeEditMode(Integration $integration, $newEditMode)
    {
        $allowed = [Integration::EDIT_MODE_DISALLOW, Integration::EDIT_MODE_RESTRICTED, Integration::EDIT_MODE_ALLOW];

        if (in_array($integration->getEditMode(), $allowed, true)) {
            $integration->setEditMode($newEditMode);
        }
    }

    /**
     * Checks if edit mode allow to activate/deactivate integration
     *
     * @param int $editMode
     *
     * @return bool
     */
    public static function isSwitchEnableAllowed($editMode)
    {
        $allowed = [Integration::EDIT_MODE_RESTRICTED, Integration::EDIT_MODE_ALLOW];

        return in_array($editMode, $allowed, true);
    }
}
