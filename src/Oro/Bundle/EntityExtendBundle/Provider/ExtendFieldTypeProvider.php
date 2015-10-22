<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

class ExtendFieldTypeProvider
{
    const GROUP_FIELDS = 'fields';
    const GROUP_RELATIONS = 'relations';

    /** @var array */
    protected $types = [];

    /**
     * @param array $fields
     * @param array $relations
     */
    public function __construct(array $fields = [], array $relations = [])
    {
        $this->types = [self::GROUP_FIELDS => $fields, self::GROUP_RELATIONS => $relations];
    }

    /**
     * @return array
     */
    public function getSupportedFieldTypes()
    {
        return $this->types[self::GROUP_FIELDS];
    }

    /**
     * @return array
     */
    public function getSupportedRelationTypes()
    {
        return $this->types[self::GROUP_FIELDS];
    }
}
