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
            'file',
            'image'
        ]
    ];

    protected $realRelationTypes = [
        'ref-one',
        'ref-many',
        'manyToOne',
        'oneToMany',
        'manyToMany'
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

    /**
     * Check if relation is real relation
     *
     * @param string $type
     *
     * @return bool
     */
    public function isRealRelation($type)
    {
        return in_array($type, $this->realRelationTypes);
    }
}
