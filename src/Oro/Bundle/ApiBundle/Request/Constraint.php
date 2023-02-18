<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides request validation constrains that are used the most often.
 */
final class Constraint
{
    public const FILTER = 'filter constraint';
    public const SORT = 'sort constraint';
    public const REQUEST_DATA = 'request data constraint';
    public const REQUEST_TYPE = 'request type constraint';
    public const VALUE = 'value constraint';
    public const ENTITY = 'entity constraint';
    public const ENTITY_TYPE = 'entity type constraint';
    public const ENTITY_ID = 'entity identifier constraint';
    public const CONFLICT = 'conflict constraint';
    public const FORM = 'form constraint';
    public const EXTRA_FIELDS = 'extra fields constraint';
    public const RELATIONSHIP = 'relationship constraint';
}
