<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Provides the list of all API public actions that are implemented "out of the box".
 */
final class ApiAction
{
    public const OPTIONS = 'options';
    public const GET = 'get';
    public const GET_LIST = 'get_list';
    public const UPDATE = 'update';
    public const UPDATE_LIST = 'update_list';
    public const CREATE = 'create';
    public const DELETE = 'delete';
    public const DELETE_LIST = 'delete_list';
    public const GET_SUBRESOURCE = 'get_subresource';
    public const UPDATE_SUBRESOURCE = 'update_subresource';
    public const ADD_SUBRESOURCE = 'add_subresource';
    public const DELETE_SUBRESOURCE = 'delete_subresource';
    public const GET_RELATIONSHIP = 'get_relationship';
    public const UPDATE_RELATIONSHIP = 'update_relationship';
    public const ADD_RELATIONSHIP = 'add_relationship';
    public const DELETE_RELATIONSHIP = 'delete_relationship';
}
