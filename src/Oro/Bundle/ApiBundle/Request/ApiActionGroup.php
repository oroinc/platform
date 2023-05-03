<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides the list of groups for all API public actions that are implemented "out of the box".
 */
final class ApiActionGroup
{
    public const INITIALIZE = 'initialize';
    public const FINALIZE = 'finalize';
    public const RESOURCE_CHECK = 'resource_check';
    public const SECURITY_CHECK = 'security_check';
    public const DATA_SECURITY_CHECK = 'data_security_check';
    public const BUILD_QUERY = 'build_query';
    public const LOAD_DATA = 'load_data';
    public const SAVE_DATA = 'save_data';
    public const DELETE_DATA = 'delete_data';
    public const TRANSFORM_DATA = 'transform_data';
    public const NORMALIZE_DATA = 'normalize_data';
    public const NORMALIZE_INPUT = 'normalize_input';
    public const NORMALIZE_RESULT = 'normalize_result';
    public const SAVE_ERRORS = 'save_errors';
}
