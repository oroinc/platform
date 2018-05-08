<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides request validation constrains that are used the most often.
 */
final class Constraint
{
    const FILTER       = 'filter constraint';
    const SORT         = 'sort constraint';
    const REQUEST_DATA = 'request data constraint';
    const REQUEST_TYPE = 'request type constraint';
    const VALUE        = 'value constraint';
    const ENTITY       = 'entity constraint';
    const ENTITY_TYPE  = 'entity type constraint';
    const ENTITY_ID    = 'entity identifier constraint';
    const CONFLICT     = 'conflict constraint';
    const FORM         = 'form constraint';
    const EXTRA_FIELDS = 'extra fields constraint';
    const RELATIONSHIP = 'relationship constraint';
}
