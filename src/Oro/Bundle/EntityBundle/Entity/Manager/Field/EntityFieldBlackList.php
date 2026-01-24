<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

/**
 * Defines fields that cannot be edited through the entity field manager.
 *
 * This class maintains a blacklist of system fields (`id`, `createdAt`, `updatedAt`) that
 * should not be directly editable through the entity field update API. It provides
 * a centralized list of protected fields that must not be modified by users.
 */
class EntityFieldBlackList
{
    const EDIT_BLACK_LIST_ID         = 'id';
    const EDIT_BLACK_LIST_CREATED_AT = 'createdAt';
    const EDIT_BLACK_LIST_UPDATED_AT = 'updatedAt';

    /**
     * @return array
     */
    public static function getValues()
    {
        return [
            EntityFieldBlackList::EDIT_BLACK_LIST_ID,
            EntityFieldBlackList::EDIT_BLACK_LIST_CREATED_AT,
            EntityFieldBlackList::EDIT_BLACK_LIST_UPDATED_AT,
        ];
    }
}
