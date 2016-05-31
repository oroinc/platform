<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * The most often used request validation constrains.
 */
final class Constraint
{
    const FILTER       = 'filter constraint';
    const SORT         = 'sort constraint';
    const REQUEST_DATA = 'request data constraint';
    const REQUEST_TYPE = 'request type constraint';
    const ENTITY_TYPE  = 'entity type constraint';
    const ENTITY_ID    = 'entity identifier constraint';
    const FORM         = 'form constraint';
    const EXTRA_FIELDS = 'extra fields constraint';
}
