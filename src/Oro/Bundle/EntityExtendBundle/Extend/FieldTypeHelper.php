<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * This class should help with detecting relation field types
 *
 * TODO: implement type mapping via configuration yamls
 */
class FieldTypeHelper
{
    protected $typeMap = [
        'manyToOne' => [
            'attachment',
            'attachmentImage'
        ]
    ];

    /**
     * @param string $type
     *
     * @return string
     */
    public function getUnderlyingType($type)
    {
        foreach ($this->typeMap as $key => $value) {
            if (in_array($type, $value)) {
                return $key;
            }
        }

        return $type;
    }
}
