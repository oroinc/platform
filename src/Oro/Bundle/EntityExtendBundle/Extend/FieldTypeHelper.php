<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

class FieldTypeHelper
{
    /**
     * @var string[]
     *      key   = data type name
     *      value = underlying data type
     */
    public $underlyingTypesMap;

    /**
     * @param string[] $underlyingTypesMap key = data type name, value = underlying data type
     */
    public function __construct($underlyingTypesMap)
    {
        $this->underlyingTypesMap = $underlyingTypesMap;
    }

    /**
     * Check if given form type is relation
     *
     * @param string $type
     *
     * @return bool
     */
    public static function isRelation($type)
    {
        return in_array($type, ['ref-one', 'ref-many', 'oneToMany', 'manyToOne', 'manyToMany', 'optionSet']);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public function getUnderlyingType($type)
    {
        if (!isset($this->underlyingTypesMap[$type])) {
            return $type;
        }

        return $this->underlyingTypesMap[$type];
    }
}
