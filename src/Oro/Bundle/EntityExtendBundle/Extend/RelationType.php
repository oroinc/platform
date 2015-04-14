<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

class RelationType
{
    /**
     * Identifies a one-to-one association.
     */
    const ONE_TO_ONE = 'oneToOne';

    /**
     * Identifies a many-to-one association.
     */
    const MANY_TO_ONE = 'manyToOne';

    /**
     * Identifies a multiple many-to-one association.
     */
    const MULTIPLE_MANY_TO_ONE = 'multipleManyToOne';

    /**
     * Identifies a one-to-many association.
     */
    const ONE_TO_MANY = 'oneToMany';

    /**
     * Identifies a many-to-many association.
     */
    const MANY_TO_MANY = 'manyToMany';

    /**
     * Identifies a to-one (single-valued) associations.
     */
    const TO_ONE = 'ref-one';

    /**
     * Identifies a to-many (collection-valued) associations.
     */
    const TO_MANY = 'ref-many';

    /**
     * Represents to-many relations
     *
     * @var array
     */
    public static $toManyRelations = [
        self::ONE_TO_MANY,
        self::MANY_TO_MANY,
        self::TO_MANY,
    ];

    /**
     * Represents to-one relations
     *
     * @var array
     */
    public static $toOneRelations = [
        self::ONE_TO_ONE,
        self::MANY_TO_ONE,
        self::TO_ONE,
    ];

    /**
     * Represents to-* relations
     *
     * @var array
     */
    public static $toAnyRelations = [
        self::TO_ONE,
        self::TO_MANY,
    ];

    /**
     * Represents *-* relations
     *
     * @var array
     */
    public static $anyToAnyRelations = [
        self::ONE_TO_ONE,
        self::MANY_TO_ONE,
        self::ONE_TO_MANY,
        self::MANY_TO_MANY,
    ];
}
