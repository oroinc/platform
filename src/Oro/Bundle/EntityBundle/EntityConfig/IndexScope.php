<?php

namespace Oro\Bundle\EntityBundle\EntityConfig;

/**
 * Defines index type constants for entity fields.
 *
 * This class provides constants that specify the type of database index to create
 * for entity fields, supporting both simple and unique indexes.
 */
class IndexScope
{
    const INDEX_SIMPLE = 1;
    const INDEX_UNIQUE = 2;
}
