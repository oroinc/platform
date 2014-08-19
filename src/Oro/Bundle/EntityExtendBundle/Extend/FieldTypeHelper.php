<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

class FieldTypeHelper
{
    /**
     * @var string[]
     *      key   = data type name
     *      value = underlying data type
     */
    protected $underlyingTypesMap;

    protected $realRelationTypes = [
        'ref-one'    => true,
        'ref-many'   => true,
        'manyToOne'  => true,
        'oneToMany'  => true,
        'manyToMany' => true
    ];

    /**
     * @param string[] $underlyingTypesMap key = data type name, value = underlying data type
     */
    public function __construct($underlyingTypesMap)
    {
        $this->underlyingTypesMap = $underlyingTypesMap;
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

    /**
     * Check if relation is real relation
     *
     * @param string $type
     *
     * @return bool
     */
    public function isRealRelation($type)
    {
        return isset($this->realRelationTypes[$type]);
    }
}
