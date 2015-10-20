<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

class EntityFieldBlackList
{
    const INLINE_EDIT_BLACK_LIST_ID         = 'id';
    const INLINE_EDIT_BLACK_LIST_CREATED_AT = 'createdAt';
    const INLINE_EDIT_BLACK_LIST_UPDATED_AT = 'updatedAt';

    /**
     * @return array
     */
    public static function getValues()
    {
        return [
            EntityFieldBlackList::INLINE_EDIT_BLACK_LIST_ID,
            EntityFieldBlackList::INLINE_EDIT_BLACK_LIST_CREATED_AT,
            EntityFieldBlackList::INLINE_EDIT_BLACK_LIST_UPDATED_AT,
        ];
    }
}
