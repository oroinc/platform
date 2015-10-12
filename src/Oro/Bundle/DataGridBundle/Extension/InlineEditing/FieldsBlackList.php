<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

class FieldsBlackList
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
            FieldsBlackList::INLINE_EDIT_BLACK_LIST_ID,
            FieldsBlackList::INLINE_EDIT_BLACK_LIST_CREATED_AT,
            FieldsBlackList::INLINE_EDIT_BLACK_LIST_UPDATED_AT,
        ];
    }
}
