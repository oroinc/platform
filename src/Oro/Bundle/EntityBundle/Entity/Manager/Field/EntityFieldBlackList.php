<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

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
